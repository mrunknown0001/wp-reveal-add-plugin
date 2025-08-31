/**
 * WordPress Reveal Ad JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const revealAd = $('#revealAd');
        const body = $('body');
        const adClose = $('#adClose');
        
        // Get settings from WordPress
        const settings = typeof revealAdSettings !== 'undefined' ? revealAdSettings : {
            enabled: '1',
            delay: '500',
            height: '200',
            scroll_threshold: '100'
        };
        
        if (settings.enabled !== '1' || revealAd.length === 0) {
            return;
        }
        
        let lastScrollTop = 0;
        let adClosed = false;
        let adRevealed = false;
        let isScrolling = false;
        
        // Calculate heights for WordPress compatibility
        const adminBarHeight = body.hasClass('admin-bar') ? 
            ($(window).width() > 782 ? 32 : 46) : 0;
        const adHeight = parseInt(settings.height, 10);
        const totalOffset = adminBarHeight + adHeight;
        
        // Set dynamic ad height
        revealAd.css('height', adHeight + 'px');
        
        // Mobile responsive height
        if ($(window).width() <= 768) {
            const mobileHeight = Math.floor(adHeight * 0.75);
            revealAd.css('height', mobileHeight + 'px');
        }
        
        // Scroll throttling for better performance
        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }
        
        // Initial reveal animation after page load
        $(window).on('load', function() {
            setTimeout(function() {
                if (!adClosed) {
                    showAd();
                    
                    // Handle Google Ads loading
                    const adType = revealAd.data('ad-type');
                    if (adType === 'google_adsense') {
                        handleGoogleAdsense();
                    } else if (adType === 'google_custom') {
                        handleCustomGoogleAds();
                    }
                }
            }, parseInt(settings.delay, 10));
        });
        
        // Handle Google AdSense loading
        function handleGoogleAdsense() {
            const adsenseAd = revealAd.find('.adsbygoogle');
            if (adsenseAd.length > 0) {
                // Ensure AdSense script is loaded
                if (typeof adsbygoogle !== 'undefined') {
                    try {
                        // Push the ad to the AdSense queue if not already done
                        if (!adsenseAd.hasClass('adsbygoogle-pushed')) {
                            (adsbygoogle = window.adsbygoogle || []).push({});
                            adsenseAd.addClass('adsbygoogle-pushed');
                        }
                        
                        // Monitor ad loading
                        const checkAdLoaded = setInterval(function() {
                            if (adsenseAd.find('iframe').length > 0 || adsenseAd.attr('data-adsbygoogle-status')) {
                                clearInterval(checkAdLoaded);
                                adjustAdSize();
                            }
                        }, 100);
                        
                        // Stop checking after 10 seconds
                        setTimeout(function() {
                            clearInterval(checkAdLoaded);
                        }, 10000);
                        
                    } catch (e) {
                        console.warn('AdSense loading error:', e);
                        showAdError('AdSense failed to load');
                    }
                } else {
                    setTimeout(handleGoogleAdsense, 500); // Retry after 500ms
                }
            }
        }
        
        // Handle custom Google Ads
        function handleCustomGoogleAds() {
            // For custom ad codes, we'll let them load naturally
            // but monitor for any scripts that need to be executed
            setTimeout(function() {
                adjustAdSize();
            }, 1000);
        }
        
        // Adjust ad container size based on content
        function adjustAdSize() {
            const adContent = revealAd.find('.ad-content');
            const adType = revealAd.data('ad-type');
            
            if (adType !== 'custom') {
                // For Google Ads, ensure proper sizing
                const iframe = adContent.find('iframe');
                if (iframe.length > 0) {
                    const iframeHeight = iframe.height() || iframe.attr('height');
                    if (iframeHeight && parseInt(iframeHeight) > 0) {
                        const newHeight = Math.min(parseInt(iframeHeight) + 40, 400); // Add padding, max 400px
                        revealAd.css('height', newHeight + 'px');
                        
                        // Update body padding
                        if (adRevealed && !adClosed) {
                            const adminBarHeight = body.hasClass('admin-bar') ? 
                                ($(window).width() > 782 ? 32 : 46) : 0;
                            body.css('padding-top', (adminBarHeight + newHeight) + 'px');
                        }
                    }
                }
            }
        }
        
        // Show ad error message
        function showAdError(message) {
            const adContent = revealAd.find('.ad-content');
            adContent.html('<div style="text-align: center; color: #666; padding: 20px;"><p>' + message + '</p><p style="font-size: 14px;">Check your ad configuration in WordPress admin.</p></div>');
        }
        
        // Show ad function
        function showAd() {
            revealAd.removeClass('hidden').addClass('revealed');
            body.addClass('reveal-ad-active');
            
            // Adjust body padding for the ad
            const currentAdHeight = revealAd.outerHeight();
            body.css('padding-top', (adminBarHeight + currentAdHeight) + 'px');
            
            adRevealed = true;
            
            // Track ad impression (Google Analytics example)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'ad_impression', {
                    'event_category': 'reveal_ad',
                    'event_label': 'top_banner'
                });
            }
        }
        
        // Hide ad function
        function hideAd() {
            revealAd.removeClass('revealed').addClass('hidden');
            body.removeClass('reveal-ad-active');
            body.css('padding-top', adminBarHeight + 'px');
        }
        
        // Close button functionality
        adClose.on('click', function(e) {
            e.stopPropagation();
            hideAd();
            adClosed = true;
            
            // Store in session storage to remember user preference
            try {
                sessionStorage.setItem('revealAdClosed', 'true');
            } catch(e) {
                // Fallback if sessionStorage is not available
                document.cookie = 'revealAdClosed=true; path=/; max-age=3600';
            }
            
            // Track ad close event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'ad_close', {
                    'event_category': 'reveal_ad',
                    'event_label': 'user_closed'
                });
            }
        });
        
        // Check if user previously closed the ad in this session
        function checkAdClosedStatus() {
            try {
                return sessionStorage.getItem('revealAdClosed') === 'true';
            } catch(e) {
                // Fallback to cookie check
                return document.cookie.indexOf('revealAdClosed=true') !== -1;
            }
        }
        
        // Initialize with user preference
        if (checkAdClosedStatus()) {
            adClosed = true;
        }
        
        // Scroll behavior with throttling
        const handleScroll = throttle(function() {
            if (adClosed || !adRevealed) return;
            
            const scrollTop = $(window).scrollTop();
            const scrollThreshold = parseInt(settings.scroll_threshold, 10);
            
            if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
                // Scrolling down - hide ad
                if (revealAd.hasClass('revealed')) {
                    hideAd();
                }
            } else if (scrollTop < lastScrollTop || scrollTop <= 50) {
                // Scrolling up or near top - show ad
                if (!revealAd.hasClass('revealed')) {
                    showAd();
                }
            }
            
            lastScrollTop = scrollTop;
        }, 16); // ~60fps
        
        $(window).on('scroll', handleScroll);
        
        // Ad click functionality
        revealAd.on('click', function(e) {
            if ($(e.target).is(adClose) || $(e.target).closest('.ad-close').length) {
                return; // Don't trigger ad click if close button was clicked
            }
            
            const adLink = revealAd.data('link');
            if (adLink && adLink !== '#') {
                // Track ad click
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'ad_click', {
                        'event_category': 'reveal_ad',
                        'event_label': 'banner_click'
                    });
                }
                
                // Open link
                if (adLink.startsWith('http')) {
                    window.open(adLink, '_blank', 'noopener,noreferrer');
                } else {
                    window.location.href = adLink;
                }
            }
        });
        
        // Window resize handler
        $(window).on('resize', throttle(function() {
            if (adRevealed && !adClosed) {
                const currentAdHeight = revealAd.outerHeight();
                body.css('padding-top', (adminBarHeight + currentAdHeight) + 'px');
            }
        }, 250));
        
        // Accessibility: ESC key to close ad
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && adRevealed && !adClosed) {
                adClose.trigger('click');
            }
        });
        
        // Intersection Observer for better performance (if supported)
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && !adClosed) {
                        // Ad is visible
                        revealAd.addClass('in-view');
                    } else {
                        revealAd.removeClass('in-view');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            if (revealAd[0]) {
                observer.observe(revealAd[0]);
            }
        }
        
        // Clean up on page unload
        $(window).on('beforeunload', function() {
            $(window).off('scroll', handleScroll);
            $(window).off('resize');
        });
    });
    
})(jQuery);