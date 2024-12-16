import {Button} from 'native-base';
import React from 'react';
import {navigate} from '../../../helpers/RootNavigator';

export const StartLocalIllRequest = (props) => {
	const openLocalIllRequest = () => {
		navigate('CreateLocalIllRequest', {
			id: props.record,
			workTitle: props.workTitle
		});
	};

	return (
		<Button
			size="md"
			bgColor={theme['colors']['primary']['500']}
			variant="solid"
			minWidth="100%"
			maxWidth="100%"
			variant="solid"
			_text={{
				padding: 0,
				textAlign: 'center',
			}}
			style={{
				flex: 1,
				flexWrap: 'wrap',
			}}
			onPress={openLocalIllRequest}>
			<ButtonText color={theme['colors']['primary']['500-text']} textAlign="center">
				Request
			</ButtonText>
		</Button>
	);
};