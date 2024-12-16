import _ from 'lodash';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Box, Button, Checkbox, CheckIcon, FormControl, Input, Select, Text, TextArea, ScrollView } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { loadingSpinner } from '../../components/loadingSpinner';
import { submitLocalIllRequest } from '../../util/recordActions';
import { HoldsContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { popAlert, loadError } from '../../components/loadError';
import { getLocalIllForm } from '../../util/loadLibrary';
import { reloadProfile } from '../../util/api/user';
import { reloadHolds } from '../../util/loadPatron';

export const CreateLocalIllRequest = () => {
     const route = useRoute();
     const id = route.params.id;
     const title = route.params.workTitle ?? null;
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { updateUser } = React.useContext(UserContext);

     if (location.localIllFormId === '-1' || _.isNull(location.localIllFormId)) {
          return loadError('The ILL System is not setup properly, please contact your library to place a request', '');
     }

     console.log("Local ILL Form Id " + location.localIllFormId);
     console.log("ID " + route.params.id);

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['localIllForm', location.localIllFormId, library.baseUrl],
          queryFn: () => getLocalIllForm(library.baseUrl, location.localIllFormId),
     });

     return <>{status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', '') : <Request config={data} workId={id} workTitle={title} />}</>;
};

const Request = (payload) => {
     const navigation = useNavigation();
     const { config, workId, workTitle } = payload;
     const { library } = React.useContext(LibrarySystemContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateHolds } = React.useContext(HoldsContext);

     const [title, setTitle] = React.useState(workTitle);
     const [note, setNote] = React.useState('');
     const [acceptFee, setAcceptFee] = React.useState(false);
     const [pickupLocation, setPickupLocation] = React.useState();

     const [isSubmitting, setIsSubmitting] = React.useState(false);

     const handleSubmission = async () => {
          const request = {
               title: title ?? null,
               acceptFee: acceptFee,
               note: note ?? null,
               catalogKey: workId ?? null,
               pickupLocation: pickupLocation ?? null,
          };
          await submitLocalIllRequest(library.baseUrl, request).then(async (result) => {
               setIsSubmitting(false);
               if (result.success) {
                    navigation.goBack();
                    await reloadHolds(library.baseUrl).then((result) => {
                         updateHolds(result);
                    });
                    await reloadProfile(library.baseUrl).then((result) => {
                         updateUser(result);
                    });
               } else {
                    popAlert(result.title, result.message, 'error');
               }
          });
     };

     const getIntroText = () => {
          const field = config.fields.introText;
          if (field.display === 'show') {
               return (
                    <Text fontSize="sm" pb={3}>
                         {field.label}
                    </Text>
               );
          }
          return null;
     };

     const getTitleField = () => {
          const field = config.fields.title;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input
                              name={field.property}
                              defaultValue={title}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(value) => {
                                   setTitle(value);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getFeeInformation = () => {
          const field = config.fields.feeInformationText;
          if (field.display === 'show' && !_.isEmpty(field.label)) {
               return <Text bold>{field.label}</Text>;
          }
          return null;
     };

     const getAcceptFeeCheckbox = () => {
          const field = config.fields.acceptFee;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} maxW="90%" isRequired={field.required}>
                         <Checkbox
                              name={field.property}
                              accessibilityLabel={field.description ?? field.label}
                              onChange={(value) => {
                                   setAcceptFee(value);
                              }}
                              value>
                              {field.label}
                         </Checkbox>
                    </FormControl>
               );
          }
          return null;
     };

     const getNoteField = () => {
          const field = config.fields.note;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <TextArea
                              name={field.property}
                              value={note}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(text) => {
                                   setNote(text);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getPickupLocations = () => {
          const field = config.fields.pickupLocation;
          if (field.display === 'show' && _.isArray(field.options)) {
               const locations = field.options;
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Select
                              isReadOnly={Platform.OS === 'android'}
                              name="pickupLocation"
                              defaultValue={pickupLocation}
                              accessibilityLabel={field.description ?? field.label}
                              _selectedItem={{
                                   bg: 'tertiary.300',
                                   endIcon: <CheckIcon size="5" />,
                              }}
                              selectedValue={pickupLocation}
                              onValueChange={(itemValue) => {
                                   setPickupLocation(itemValue);
                              }}>
                              {locations.map((location, index) => {
                                   return <Select.Item label={location.displayName} value={location.locationId} />;
                              })}
                         </Select>
                    </FormControl>
               );
          }
          return null;
     };

     const getCatalogKeyField = () => {
          const field = config.fields.catalogKey;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isDisabled isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input name={field.property} defaultValue={catalogKey} accessibilityLabel={field.description ?? field.label} />
                    </FormControl>
               );
          }
          return null;
     };

     const getActions = () => {
          return (
               <Button.Group pt={3}>
                    <Button
                         colorScheme="secondary"
                         isLoading={isSubmitting}
                         isLoadingText={config.buttonLabelProcessing}
                         onPress={() => {
                              setIsSubmitting(true);
                              handleSubmission();
                         }}>
                         {config.buttonLabel}
                    </Button>
                    <Button colorScheme="secondary" variant="outline" onPress={() => navigation.goBack()}>
                         Cancel
                    </Button>
               </Button.Group>
          );
     };

     return (
          <ScrollView>
               <Box safeArea={5}>
                    {getIntroText()}
                    {getTitleField()}
                    {getNoteField()}
                    {getFeeInformation()}
                    {getAcceptFeeCheckbox()}
                    {getPickupLocations()}
                    {getCatalogKeyField()}
                    {getActions()}
               </Box>
          </ScrollView>
     );
};