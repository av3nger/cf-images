/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { post } from '../js/helpers/post';

const ERROR_TIMEOUT = 10000;
const SUCCESS_TIMEOUT = 4000;

/**
 * Hook for API save operations with loading, success, and error state management.
 */
export function useApiSave() {
	const [saving, setSaving] = useState(false);
	const [done, setDone] = useState(false);
	const [error, setError] = useState('');

	const errorTimer = useRef<ReturnType<typeof setTimeout>>(undefined);
	const doneTimer = useRef<ReturnType<typeof setTimeout>>(undefined);

	// Clear timers on unmount.
	useEffect(() => {
		return () => {
			clearTimeout(errorTimer.current);
			clearTimeout(doneTimer.current);
		};
	}, []);

	const execute = useCallback((action: string, data?: object | string) => {
		setError('');
		setSaving(true);

		clearTimeout(errorTimer.current);
		clearTimeout(doneTimer.current);

		post(action, data)
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					errorTimer.current = setTimeout(
						() => setError(''),
						ERROR_TIMEOUT
					);
				} else {
					setDone(true);
					doneTimer.current = setTimeout(
						() => setDone(false),
						SUCCESS_TIMEOUT
					);
				}
			})
			.catch(window.console.log)
			.finally(() => setSaving(false));
	}, []);

	return { saving, done, error, execute };
}
