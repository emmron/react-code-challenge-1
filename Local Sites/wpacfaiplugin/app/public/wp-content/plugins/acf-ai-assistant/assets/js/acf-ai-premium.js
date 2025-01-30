( function( $ ) {
    'use strict';
    
    const PremiumFeatures = {
        init: function() {
            // Wait for ACF to be ready
            acf.addAction('ready', this.onACFReady.bind(this));
        },
        
        onACFReady: function() {
            // Safe to interact with ACF API
            this.bindImageGeneration();
            this.bindSEOAnalysis();
            this.bindContentTranslation();
            this.bindPersonalization();
            this.handlePremiumUI();
            
            // Use ACF's localization data
            const postID = acf.get('post_id');
            console.log('Editing post:', postID);
        },

        handlePremiumUI: function() {
            $('.acf-ai-premium-feature').each(function() {
                const $feature = $(this);
                if (!acf_ai_assistant.is_premium) {
                    $feature.find('.premium-overlay').show();
                    $feature.find('.ai-trigger').prop('disabled', true);
                }
            });
        },

        bindImageGeneration: function() {
            $(document).on('click', '.ai-generate-images', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.acf-field-ai-images');
                const $content = $container.find('.ai-image-prompt');
                const $loading = $container.find('.ai-loading');
                const $results = $container.find('.ai-image-results');
                
                PremiumFeatures.generateImages(
                    $content.val(),
                    $container.data('style'),
                    $loading,
                    $results,
                    $container.data('nonce')
                );
            });
        },

        generateImages: function(prompt, style, $loading, $results, nonce) {
            if (!prompt) {
                this.showError($results.closest('.acf-field').find('.ai-error'), acf_ai_assistant.empty_prompt);
                return;
            }

            this.showLoading($loading);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acf_ai_handle_automatic_images',
                    prompt: prompt,
                    style: style,
                    nonce: nonce
                },
                success: (response) => {
                    if (response.success) {
                        let html = '<div class="ai-image-grid">';
                        response.data.images.forEach(img => {
                            html += `<div class="ai-image-item">
                                <img src="${img.url}" alt="${img.alt}">
                                <button class="button ai-insert-image" data-url="${img.url}">Insert</button>
                            </div>`;
                        });
                        $results.html(html + '</div>');
                    } else {
                        this.showError($results.closest('.acf-field').find('.ai-error'), response.data.message);
                    }
                },
                error: () => {
                    this.showError($results.closest('.acf-field').find('.ai-error'), acf_ai_assistant.error_message);
                },
                complete: () => {
                    this.hideLoading($loading);
                }
            });
        },

        bindSEOAnalysis: function() {
            $(document).on('click', '.ai-run-seo-analysis', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.acf-field-ai-seo');
                const $content = $container.find('textarea');
                const $keywords = $container.find('.ai-seo-keywords');
                const $loading = $container.find('.ai-loading');
                const $results = $container.find('.ai-seo-results');

                PremiumFeatures.analyzeSEO(
                    $content.val(),
                    $keywords.val(),
                    $loading,
                    $results,
                    $container.data('nonce')
                );
            });
        },

        analyzeSEO: function(content, keywords, $loading, $results, nonce) {
            this.showLoading($loading);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acf_ai_handle_seo_analysis',
                    content: content,
                    keywords: keywords,
                    nonce: nonce
                },
                success: (response) => {
                    if (response.success) {
                        $results.html(`<div class="seo-report">${response.data.analysis}</div>`);
                    } else {
                        this.showError($results.closest('.acf-field').find('.ai-error'), response.data.message);
                    }
                },
                error: () => {
                    this.showError($results.closest('.acf-field').find('.ai-error'), acf_ai_assistant.error_message);
                },
                complete: () => {
                    this.hideLoading($loading);
                }
            });
        },

        bindContentTranslation: function() {
            $(document).on('click', '.ai-translate-content', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.acf-field-ai-translation');
                const $content = $container.find('textarea');
                const $language = $container.find('.ai-translation-language');
                const $loading = $container.find('.ai-loading');
                const $output = $container.find('.ai-translation-result');

                PremiumFeatures.translateContent(
                    $content.val(),
                    $language.val(),
                    $loading,
                    $output,
                    $container.data('nonce')
                );
            });
        },

        translateContent: function(content, language, $loading, $output, nonce) {
            if (!content) {
                this.showError($output.closest('.acf-field').find('.ai-error'), acf_ai_assistant.empty_prompt);
                return;
            }

            this.showLoading($loading);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acf_ai_handle_content_translation',
                    content: content,
                    language: language,
                    nonce: nonce
                },
                success: (response) => {
                    if (response.success) {
                        $output.val(response.data.translation).trigger('change');
                    } else {
                        this.showError($output.closest('.acf-field').find('.ai-error'), response.data.message);
                    }
                },
                error: () => {
                    this.showError($output.closest('.acf-field').find('.ai-error'), acf_ai_assistant.error_message);
                },
                complete: () => {
                    this.hideLoading($loading);
                }
            });
        },

        showLoading: function($element) {
            $element.addClass('visible').html('<div class="spinner is-active"></div>');
        },

        hideLoading: function($element) {
            $element.removeClass('visible').empty();
        },

        showError: function($element, message) {
            $element.addClass('visible').html(`<div class="notice notice-error">${message}</div>`);
        }
    };

    // Initialize when DOM ready and ACF available
    $(document).ready(() => {
        if(typeof acf !== 'undefined') {
            PremiumFeatures.init();
        }
    });
})(jQuery); 