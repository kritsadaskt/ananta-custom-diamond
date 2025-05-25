import { useBlockProps, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function DiamondsBlock() {
	const blockProps = useBlockProps();

	return <div {...blockProps}>Hello World</div>;
}
