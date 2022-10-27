import * as React from "react";
import {
	Badge,
	Box,
	Button,
	Center,
	FlatList,
	Heading,
	HStack,
	Icon,
	Image,
	Pressable,
	Stack,
	Text,
	VStack
} from "native-base";
import {SafeAreaView} from 'react-native';
import {CommonActions} from '@react-navigation/native';
import _ from "lodash";
import {MaterialIcons} from "@expo/vector-icons";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {getAvailableFacets, getSearchResults, SEARCH, searchResults} from "../../util/search";
import {getLists} from "../../util/loadPatron";
import {AddToList} from "./AddToList";
import {userContext} from "../../context/user";
import {GLOBALS} from "../../util/globals";
import {LIBRARY} from "../../util/loadLibrary";

window.addEventListener = x => x
window.removeEventListener = x => x

export default class Results extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
            isLoading: true,
            isLoadingMore: false,
            data: [],
            searchMessage: null,
            page: 1,
            hasError: false,
            error: null,
            refreshing: false,
            filtering: false,
			endOfResults: false,
			dataMessage: null,
			lastListUsed: 0,
			scrollPosition: 0,
			discoveryVersion: this.props.route.params?.discoveryVersion ?? "22.10.00",
			facetSet: [],
			categorySelected: null,
			sortList: [],
			appliedSort: 'relevance',
			filters: [],
			query: this.props.route.params?.term ?? "",
			pendingFilters: this.props.route.params?.pendingFilters ?? [],
			totalResults: 0,
			paramsToAdd: this.props.route.params?.pendingParams ?? [],
			lookfor: this.props.route.params?.term ?? '%20',
			lookfor_clean: "",
		};
		//this._getLastListUsed();
		GLOBALS.pendingSearchFilters = [];
		this._facetsNum = 0;
		this._facets = [];
		this._sortOptions = [];
		this._allOptions = [];
		this._isMounted = false;
		this.lastListUsed = 0;
		this.solrScope = null;
		this.locRef = React.createRef({});
		this.updateLastListUsed = this.updateLastListUsed.bind(this);
	}

	componentDidMount = async () => {
		this._isMounted = true;

		const {lookfor} = this.state;
		this.setState({
			lookfor_clean: lookfor.replace(/" "/g, "%20"),
		})

		if (this._isMounted) {
			await getLists(this.context.library.baseUrl)
			this._getLastListUsed();
			if (LIBRARY.version >= "22.11.00") {
				await this.startSearch();
			} else {
				await this._fetchResults();
			}
		}
		this._isMounted && this.getCurrentSort();
	};


	getSnapshotBeforeUpdate(prevProps, prevState) {
		// Are we adding new items to the list?
		// Capture the scroll position so we can adjust scroll later.
		if (prevState.lastListUsed !== this.state.lastListUsed) {
			const page = this.locRef.current;
			return page.scrollHeight - page.scrollTop;
		}
		return null;
	}

	async componentDidUpdate(prevProps, prevState, snapshot) {
		if (prevProps.route.params?.pendingParams !== this.props.route.params?.pendingParams) {
			if (this._isMounted && LIBRARY.version >= "22.11.00") {
				this.setState({
					isLoading: true,
				})
				await this.startSearch();
			}
		}
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	_getLastListUsed = () => {
		if(this.context.user) {
			const user = this.context.user;
			this.lastListUsed = user.lastListUsed;
		}
	}

	updateLastListUsed = (id) => {
		this.setState({
			isLoading: true,
		})

		this.lastListUsed = id;

		this.setState({
			isLoading: false,
		})
	}

	getCurrentSort = () => {
		return _.filter(SEARCH.sortList, 'selected');
	}

	// search function for discovery 22.11.x or newer
	startSearch = async () => {
		const {lookfor_clean, page, paramsToAdd} = this.state;
		await getSearchResults(lookfor_clean, 25, page, this.context.library.baseUrl, paramsToAdd).then(data => {
			if (data.success) {
				if (data.count > 0) {
					if (data.page_current < data.page_total) {
						this.setState((prevState, nextProps) => ({
							data:
								page === 1
									? Array.from(data.items) : [...this.state.data, ...data.items],
							refreshing: false,
							isLoading: false,
							isLoadingMore: false,
							totalResults: data.totalResults,
							curPage: data.page_current,
							totalPages: data.page_total,
						}));
					} else if (data.page_current === data.page_total) {
						this.setState((prevState, nextProps) => ({
							data:
								page === 1
									? Array.from(data.items) : [...this.state.data, ...data.items],
							refreshing: false,
							isLoading: false,
							isLoadingMore: false,
							totalResults: data.totalResults,
							curPage: data.page_current,
							totalPages: data.page_total,
							dataMessage: data.message ?? translate('error.message'),
							endOfResults: true,
						}));
					} else {
						this.setState({
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: data.message ?? translate('error.message'),
							endOfResults: true,
						});
					}
				} else {
					/* No search results were found */
					this.setState({
						hasError: true,
						error: data.message,
						isLoading: false,
						isLoadingMore: false,
						refreshing: false,
						dataMessage: data.message,
					});
				}
			} else {
				this.setState({
					isLoading: false,
					hasError: true,
					error: data.error.message
				})
			}
		})

		await getAvailableFacets();
	}

	// search function for discovery 22.10.x or earlier
	_fetchResults = async () => {
		const { page } = this.state;
		const { navigation, route } = this.props;
		const givenSearch = route.params?.term ?? '%20';
		const libraryUrl = this.context.library.baseUrl;
		const searchTerm = givenSearch.replace(/" "/g, "%20");

		await searchResults(searchTerm, 100, page, libraryUrl).then(response => {
			if(response.ok) {
				//console.log(response.data);
				if(response.data.result.count > 0) {
					this.setState((prevState, nextProps) => ({
						data:
							page === 1
								? Array.from(response.data.result.items)
								: [...this.state.data, ...response.data.result.items],
						isLoading: false,
						isLoadingMore: false,
						refreshing: false
					}));
				} else {
					if(page === 1 && response.data.result.count === 0) {
						/* No search results were found */
						this.setState({
							hasError: true,
							error: response.data.result.message,
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: response.data.result.message,
						});
					} else {
						/* Tried to fetch next page, but end of results */
						this.setState({
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: response.data.result.message ?? "Unknown error fetching results",
							endOfResults: true,
						});
					}
				}
			}
		})
	}

	_handleLoadMore = () => {
	    this.setState(
	        (prevState, nextProps) => ({
	            page: prevState.page + 1,
	            isLoadingMore: true
	        }),
	        () => {
		        if (LIBRARY.version >= "22.11.00") {
			        this.startSearch();
		        } else {
			        this._fetchResults();
		        }
	        }
	    )
	};

	renderItem = (item, library, user, lastListUsed) => {
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.onPressItem(item.key, library, item.title)}>
				<HStack space={3}>
					<VStack>
						<Image source={{ uri: item.image }} alt={item.title} borderRadius="md" size={{base: "90px", lg: "120px"}} />
						<Badge mt={1} _text={{fontSize: 10, color: "coolGray.600"}} bgColor="warmGray.200" _dark={{ bgColor: "coolGray.900", _text: {color: "warmGray.400"}}}>{item.language}</Badge>
						<AddToList
							item={item.key}
							libraryUrl={library.baseUrl}
							lastListUsed={lastListUsed}
							updateLastListUsed={this.updateLastListUsed}
						/>

					</VStack>
					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800">{translate('grouped_work.by')} {item.author}</Text> : null }
						<Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{item.itemList.map((item, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>{item.name}</Badge>;
							})}
						</Stack>
					</VStack>
				</HStack>
			</Pressable>
		)
	}

	// handles the on press action
	onPressItem = (item, library, title) => {
		const { navigation, route } = this.props;
		const libraryUrl = library.baseUrl;
		navigation.dispatch(CommonActions.navigate({
			name: 'GroupedWork',
			params: {
				id: item,
				title: title,
				libraryUrl: libraryUrl,
			},
		}));
	};

    // this one shouldn't probably ever load with the catches in the render, but just in case
	_listEmptyComponent = () => {
		const { navigation, route } = this.props;
		return (
            <Center flex={1}>
                <Heading pt={5}>{translate('search.no_results')}</Heading>
                <Text bold w="75%" textAlign="center">{route.params?.term}</Text>
                <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
            </Center>
		);
	};

	_renderFooter = () => {
	    if(!this.state.isLoadingMore) return null;
	    return ( loadingSpinner() );
	}

	filterBar = () => {
		const numResults = this.state.totalResults.toLocaleString();
		const filters = GLOBALS.pendingSearchFilters;
		const availableFacetClusters = GLOBALS.availableFacetClusters;

		let keys = {};
		_.forEach(availableFacetClusters, function(tempValue, tempKey) {
			const key = {[tempKey]: tempValue.label}
			keys = _.merge(keys, key);
		})

		let numGroupedKeysWithValue = 0;
		let groupedKeys = [];
		_.forEach(keys, function(tempValue, tempKey) {
			let key = _.filter(filters, tempKey);
			let keyCount = key.length;
			let groupByKey = {
				'label': tempValue,
				'key': tempKey,
				'num': keyCount,
				'facets': key,
			}
			groupedKeys = _.concat(groupedKeys, groupByKey);

			if(keyCount > 0) {
				numGroupedKeysWithValue++;
			}
		});

		if(numGroupedKeysWithValue === 0) {
			groupedKeys = [];
		}

		let resultsLabel = translate('filters.results', {num: numResults});
		if(this.state.totalResults === 1) {
			resultsLabel = translate('filters.result', {num: numResults});
		}

		if (this.state.discoveryVersion >= "22.11.00") {
			return (
				<Box safeArea={2} _dark={{bg: 'coolGray.700'}} bgColor="coolGray.100" shadow={4}>
					<FlatList
						horizontal
						data={groupedKeys}
						ListHeaderComponent={this.filterBarHeader()}
						ListEmptyComponent={this.filterBarEmpty(this._facets)}
						renderItem={({item}) => this.filterBarButton(item, availableFacetClusters)}
						keyExtractor={({item}) => _.uniqueId(item + '_')}
					/>
					<Center>
						<Text mt={3} fontSize="lg">{resultsLabel}</Text>
					</Center>
				</Box>
			)
		}
		return null;
	}

	filterBarHeader = () => {
		return(
			<Button
				size="sm"
				leftIcon={<Icon as={MaterialIcons} name="tune" size="sm" />}
				variant="solid"
				mr={1}
				isLoading={this.state.isLoading}
				onPress={() => {
					this.setState({isLoading: true})
					this.props.navigation.push('modal', {
						screen: 'Filters',
						params: {
							options: this._allOptions,
							navigation: this.props.navigation,
							term: this.state.query,
							pendingUpdates: [],
						}
					})

					this.setState({isLoading: false})
				}}
			>{translate('filters.title')}</Button>
		)
	}

	filterBarButton = (item) => {
		//todo: add sort option to bar
		if(item.num > 0) {
			const categoryData = _.filter(this._allOptions, ['category', item.key]);
			const facets = item.facets;
			const facetClusterLabel = item.label;
			let facetLabel = "";
			let appliedFacets = [];
			_.map(facets, function(item, index, array) {
				let facet = _.join(_.values(item), '');
				facet = decodeURI(facet);
				facet = facet.replace(/%20/g,' ');
				appliedFacets = _.concat(appliedFacets, facet)
			})

			facetLabel = _.join(appliedFacets, ', ');
			const label = _.truncate(facetClusterLabel + ": " + facetLabel);
			return(
				<Button
					mr={1}
					size="sm"
					vertical
					variant="outline"
					onPress={() => {
						this.props.navigation.push('modal', {
							screen: 'Facet',
							params: {
								navigation: this.props.navigation,
								term: this.state.query,
								data: categoryData[0],
								pendingUpdates: [],
								title: categoryData[0].label,
							}
						})
					}}
				>{label}</Button>
			)
		} else {
			return null;
		}
	}

	filterBarEmpty = (availableFacetClusters) => {
		const filters = getLimitedObjects(availableFacetClusters, 5);

		return(
			<Button.Group size="sm" space={1} vertical variant="outline">
				{filters.map((item, index, array) => {
					const categoryData = _.filter(this._allOptions, ['category', item.category]);
					return <Button
						onPress={() => {
							this.props.navigation.push('modal', {
								screen: 'Facet',
								params: {
									navigation: this.props.navigation,
									term: this.state.query,
									data: categoryData[0],
									pendingUpdates: [],
									title: categoryData[0].label,
								}
							})
						}}
					>{item.label}</Button>
				})}
			</Button.Group>
		)
	}

	static contextType = userContext;


	render() {
		const { navigation, route } = this.props;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError && !this.state.dataMessage) {
			return (loadError(this.state.error, null));
		}

        if (this.state.hasError && this.state.dataMessage) {
            return (
                <Center flex={1}>
                    <Heading pt={5}>{translate('search.no_results')}</Heading>
                    <Text bold w="75%" textAlign="center">{route.params?.term}</Text>
                    <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
                </Center>
            );
        }

		return (
			<SafeAreaView style={{flex: 1}}>
				<Box ref={this.locRef} flex={1}>
					{this.filterBar()}
					<FlatList
						data={this.state.data}
						ListEmptyComponent={this._listEmptyComponent()}
						renderItem={({item}) => this.renderItem(item, library, user, this.lastListUsed)}
						keyExtractor={(item) => item.key}
						ListFooterComponent={this._renderFooter}
						onEndReached={!this.state.dataMessage ? this._handleLoadMore : null} // only try to load more if no message has been set
						onEndReachedThreshold={.5}
						initialNumToRender={25}
					/>
				</Box>
			</SafeAreaView>
		);
	}
}

function getLimitedObjects(array, n) {
	let i = 0;
	let keys = [];

	_.map(array, function (item, index, array) {
		if(i < n) {
			i++;
			keys = _.concat(keys, item);
		}
	})

	return keys;
}

function setupSortOptions(payload) {
	if(_.isArrayLike(payload) || _.isObjectLike(payload)) {
		let sortList = [];
		let i = 0;

		const container = {
			key: 0,
			category: 'sort_by',
			label: translate('filters.sort_by'),
			multiSelect: false,
			count: 0,
			hasApplied: false,
			list: [],
			applied: [],
		}
		sortList.push(container);

		_.map(payload, function(item, index, array) {
			sortList[0]['list'].push({
				key: i++,
				label: item.desc,
				numResults: 0,
				isApplied: item.selected,
				value: index,
			})

			if(item.selected) {
				sortList[0]['applied'].push({
					key: 0,
					label: item.desc,
					numResults: 0,
					isApplied: item.selected,
					value: index,
				})
			}
		})

		let foundApplied = _.has(sortList[0]['list'][0], 'isApplied')
		Object.assign(sortList[0], {count: i, hasApplied: foundApplied});

		// order facets by key
		sortList = _.orderBy(sortList, ['key']);

		return {
			'facets': sortList,
			'count': _.size(sortList),
		}
	} else {
		//make sure we return something
		return {
			'facets': [],
			'count': 0,
		}
	}
}

function setupFacetClusters(payload) {
	if(_.isArrayLike(payload) || _.isObjectLike(payload)) {
		let facetClusterKey = 1;
		let facetCluster = [];
		_.forEach(payload, function(value, key) {
			const facetList = value.list;
			let facetKey = 0;
			let i = 0;

			const container = {
				key: facetClusterKey++,
				category: key,
				label: value.label ?? translate('general.unknown'),
				multiSelect: value.multiSelect ?? false,
				hasApplied: false,
				count: 0,
				list: [],
				applied: [],
			}

			facetCluster.push(container);

			let cluster = facetCluster.find(cluster => cluster.category === key);
			_.map(facetList, function(item, index, collection) {
				cluster['list'].push({
					key: facetKey++,
					label: item.display ?? translate('general.unknown'),
					value: item.value,
					numResults: item.count,
					isApplied: item.isApplied,
				})

				if(item.isApplied) {
					cluster['applied'].push({
						key: i++,
						label: item.display ?? translate('general.unknown'),
						value: item.value,
						numResults: item.count,
						isApplied: item.isApplied,
					})
				}
			})

			let foundApplied = _.size(cluster['applied']) > 0;
			Object.assign(cluster, {count: facetKey, hasApplied: foundApplied});

		})

		// order facets by key
		facetCluster = _.orderBy(facetCluster, ['key']);

		return {
			'facets': facetCluster,
			'count': _.size(facetCluster),
		}

	} else {
		//make sure we return something
		return {
			'facets': [],
			'count': 0,
		}
	}
}
