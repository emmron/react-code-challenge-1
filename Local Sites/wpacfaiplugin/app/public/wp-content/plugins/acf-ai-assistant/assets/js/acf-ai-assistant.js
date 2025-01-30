/**
 * ACF AI Assistant JavaScript
 *
 * Handles all frontend functionality for the ACF AI Assistant field.
 */
( function ( $ ) {
	'use strict';

	// Initialize on document ready
	$( document ).ready(
		function () {
			initAIAssistant();
		}
	);

	/**
	 * Initialize the AI Assistant functionality
	 */
	function initAIAssistant() {
		const $fields = $( '.acf-field-ai-assistant' );

		if ( ! $fields.length ) {
			return;
		}

		$fields.each(
			function () {
				const $field       = $( this );
				const $input       = $field.find( 'textarea' );
				const $generateBtn = $field.find( '.ai-generate' );
				const $loading     = $field.find( '.ai-loading' );
				const $error       = $field.find( '.ai-error' );
				const $suggestions = $field.find( '.ai-suggestions' );

				$generateBtn.on(
					'click',
					function ( e ) {
						e.preventDefault();
						generateContent( $field, $input, $loading, $error, $suggestions );
					}
				);

				$field.on(
					'click',
					'.ai-suggestion',
					function () {
						const suggestion = $( this ).text();
						$input.val( suggestion );
						$suggestions.removeClass( 'active' );
					}
				);
			}
		);
	}

	/**
	 * Generate content using the AI API
	 *
	 * @param {jQuery} $field Field container
	 * @param {jQuery} $input Text input element
	 * @param {jQuery} $loading Loading indicator
	 * @param {jQuery} $error Error message container
	 * @param {jQuery} $suggestions Suggestions container
	 */
	function generateContent( $field, $input, $loading, $error, $suggestions ) {
		const prompt = $input.val();
		const nonce  = $field.data( 'nonce' );

		if ( ! prompt ) {
			showError( $error, acf_ai_assistant.empty_prompt );
			return;
		}

		showLoading( $loading );
		hideError( $error );
		hideSuggestions( $suggestions );

		$.ajax(
			{
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'acf_ai_assistant_generate',
					prompt: prompt,
					nonce: nonce
				},
				success: function ( response ) {
					if ( response.success ) {
						handleSuccess( response.data, $input, $suggestions );
					} else {
						showError( $error, response.data.message );
					}
				},
				error: function () {
					showError( $error, acf_ai_assistant.error_message );
				},
				complete: function () {
					hideLoading( $loading );
				}
			}
		);
	}

	/**
	 * Handle successful API response
	 *
	 * @param {Object} data Response data
	 * @param {jQuery} $input Text input element
	 * @param {jQuery} $suggestions Suggestions container
	 */
	function handleSuccess( data, $input, $suggestions ) {
		if ( data.suggestions && data.suggestions.length ) {
			showSuggestions( data.suggestions, $suggestions );
		} else {
			$input.val( data.content );
		}
	}

	/**
	 * Show loading indicator
	 *
	 * @param {jQuery} $loading Loading indicator element
	 */
	function showLoading( $loading ) {
		$loading.addClass( 'active' );
	}

	/**
	 * Hide loading indicator
	 *
	 * @param {jQuery} $loading Loading indicator element
	 */
	function hideLoading( $loading ) {
		$loading.removeClass( 'active' );
	}

	/**
	 * Show error message
	 *
	 * @param {jQuery} $error Error container element
	 * @param {string} message Error message to display
	 */
	function showError( $error, message ) {
		$error.html( message ).addClass( 'active' );
	}

	/**
	 * Hide error message
	 *
	 * @param {jQuery} $error Error container element
	 */
	function hideError( $error ) {
		$error.removeClass( 'active' ).empty();
	}

	/**
	 * Show suggestions
	 *
	 * @param {Array} suggestions Array of suggestion strings
	 * @param {jQuery} $suggestions Suggestions container element
	 */
	function showSuggestions( suggestions, $suggestions ) {
		const $list = $( '<div class="ai-suggestion-list"></div>' );

		suggestions.forEach(
			function ( suggestion ) {
				$list.append(
					$( '<div class="ai-suggestion"></div>' ).text( suggestion )
				);
			}
		);

		$suggestions.empty().append( $list ).addClass( 'active' );
	}

	/**
	 * Hide suggestions
	 *
	 * @param {jQuery} $suggestions Suggestions container element
	 */
	function hideSuggestions( $suggestions ) {
		$suggestions.removeClass( 'active' ).empty();
	}

} )( jQuery ); 