<?php
/**
 * Plugin Name: Reveal Ad
 * Description: A smooth reveal ad that appears at the top of your site with scroll-based behavior
 * Version: 1.0.0
 * Author: Adam
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RevealAdPlugin {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_reveal_ad'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('reveal-ad-style', plugin_dir_url(__FILE__) . 'reveal-ad.css', array(), '1.0.0');
        wp_enqueue_script('reveal-ad-script', plugin_dir_url(__FILE__) . 'reveal-ad.js', array('jquery'), '1.0.0', true);
        
        // Pass settings to JavaScript
        wp_localize_script('reveal-ad-script', 'revealAdSettings', array(
            'enabled' => get_option('reveal_ad_enabled', '1'),
            'delay' => get_option('reveal_ad_delay', '500'),
            'height' => get_option('reveal_ad_height', '200'),
            'scroll_threshold' => get_option('reveal_ad_scroll_threshold', '100')
        ));
    }
    
    public function render_reveal_ad() {
        if (!get_option('reveal_ad_enabled', '1')) {
            return;
        }
        
        $ad_type = get_option('reveal_ad_type', 'custom');
        $ad_content = get_option('reveal_ad_content', '🎯 Your Amazing Ad Content Here!');
        $ad_subtitle = get_option('reveal_ad_subtitle', 'Click here for exclusive offers');
        $ad_link = get_option('reveal_ad_link', '#');
        $ad_bg_color = get_option('reveal_ad_bg_color', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
        
        // Google Ads settings
        $google_ad_client = get_option('reveal_ad_google_client', '');
        $google_ad_slot = get_option('reveal_ad_google_slot', '');
        $google_ad_format = get_option('reveal_ad_google_format', 'auto');
        $google_ad_responsive = get_option('reveal_ad_google_responsive', '1');
        $google_ad_custom_code = get_option('reveal_ad_google_custom_code', '');
        
        ?>
        
        <!-- Google AdSense Script -->
        <?php if ($ad_type === 'google_adsense' && $google_ad_client): ?>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($google_ad_client); ?>" crossorigin="anonymous"></script>
        <?php endif; ?>
        
        <div class="reveal-ad" id="revealAd" data-link="<?php echo esc_url($ad_link); ?>" data-ad-type="<?php echo esc_attr($ad_type); ?>">
            <button class="ad-close" id="adClose">&times;</button>
            <div class="ad-content">
                <?php if ($ad_type === 'custom'): ?>
                    <!-- Custom Ad Content -->
                    <div class="ad-title"><?php echo esc_html($ad_content); ?></div>
                    <?php if ($ad_subtitle): ?>
                        <p class="ad-subtitle"><?php echo esc_html($ad_subtitle); ?></p>
                    <?php endif; ?>
                    
                <?php elseif ($ad_type === 'google_adsense' && $google_ad_client && $google_ad_slot): ?>
                    <!-- Google AdSense Ad -->
                    <ins class="adsbygoogle reveal-adsense"
                         style="display:block"
                         data-ad-client="<?php echo esc_attr($google_ad_client); ?>"
                         data-ad-slot="<?php echo esc_attr($google_ad_slot); ?>"
                         data-ad-format="<?php echo esc_attr($google_ad_format); ?>"
                         <?php if ($google_ad_responsive): ?>data-full-width-responsive="true"<?php endif; ?>></ins>
                    <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                    
                <?php elseif ($ad_type === 'google_custom' && $google_ad_custom_code): ?>
                    <!-- Custom Google Ad Code -->
                    <?php echo wp_kses($google_ad_custom_code, $this->get_allowed_ad_html()); ?>
                    
                <?php else: ?>
                    <!-- Fallback content if Google Ads not configured -->
                    <div class="ad-title">Configure your Google Ads</div>
                    <p class="ad-subtitle">Go to Settings > Reveal Ad to set up your ads</p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .reveal-ad {
                background: <?php echo esc_attr($ad_bg_color); ?> !important;
                height: <?php echo esc_attr(get_option('reveal_ad_height', '200')); ?>px !important;
            }
            
            <?php if ($ad_type !== 'custom'): ?>
            .reveal-ad {
                background: #f8f9fa !important; /* Neutral background for ads */
            }
            .reveal-ad .ad-content {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .reveal-ad .reveal-adsense {
                max-width: 100%;
                max-height: calc(100% - 40px);
            }
            <?php endif; ?>
            
            @media (max-width: 768px) {
                .reveal-ad {
                    height: <?php echo esc_attr(get_option('reveal_ad_height', '200') * 0.75); ?>px !important;
                }
            }
        </style>
        
        <?php
    }
    
    /**
     * Get allowed HTML for ad codes
     */
    private function get_allowed_ad_html() {
        return array(
            'script' => array(
                'async' => array(),
                'src' => array(),
                'crossorigin' => array(),
                'type' => array(),
                'data-ad-client' => array(),
                'data-ad-slot' => array(),
                'data-ad-format' => array(),
                'data-full-width-responsive' => array()
            ),
            'ins' => array(
                'class' => array(),
                'style' => array(),
                'data-ad-client' => array(),
                'data-ad-slot' => array(),
                'data-ad-format' => array(),
                'data-full-width-responsive' => array(),
                'data-ad-layout' => array(),
                'data-ad-layout-key' => array()
            ),
            'div' => array(
                'id' => array(),
                'class' => array(),
                'style' => array(),
                'data-ad-client' => array(),
                'data-ad-slot' => array(),
                'data-ad-format' => array()
            ),
            'iframe' => array(
                'src' => array(),
                'width' => array(),
                'height' => array(),
                'frameborder' => array(),
                'scrolling' => array(),
                'allowfullscreen' => array(),
                'style' => array()
            )
        );
    }
    
    // Admin menu
    public function add_admin_menu() {
        add_options_page(
            'Reveal Ad Settings',
            'Reveal Ad',
            'manage_options',
            'reveal_ad',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('reveal_ad', 'reveal_ad_enabled');
        register_setting('reveal_ad', 'reveal_ad_type');
        register_setting('reveal_ad', 'reveal_ad_content');
        register_setting('reveal_ad', 'reveal_ad_subtitle');
        register_setting('reveal_ad', 'reveal_ad_link');
        register_setting('reveal_ad', 'reveal_ad_bg_color');
        register_setting('reveal_ad', 'reveal_ad_height');
        register_setting('reveal_ad', 'reveal_ad_delay');
        register_setting('reveal_ad', 'reveal_ad_scroll_threshold');
        
        // Google Ads settings
        register_setting('reveal_ad', 'reveal_ad_google_client');
        register_setting('reveal_ad', 'reveal_ad_google_slot');
        register_setting('reveal_ad', 'reveal_ad_google_format');
        register_setting('reveal_ad', 'reveal_ad_google_responsive');
        register_setting('reveal_ad', 'reveal_ad_google_custom_code');
    }
    
    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Reveal Ad Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('reveal_ad');
                do_settings_sections('reveal_ad');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Reveal Ad</th>
                        <td>
                            <input type="checkbox" name="reveal_ad_enabled" value="1" <?php checked(1, get_option('reveal_ad_enabled', '1'), true); ?> />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Ad Type</th>
                        <td>
                            <select name="reveal_ad_type" id="reveal_ad_type">
                                <option value="custom" <?php selected('custom', get_option('reveal_ad_type', 'custom')); ?>>Custom Content</option>
                                <option value="google_adsense" <?php selected('google_adsense', get_option('reveal_ad_type', 'custom')); ?>>Google AdSense</option>
                                <option value="google_custom" <?php selected('google_custom', get_option('reveal_ad_type', 'custom')); ?>>Custom Google Ad Code</option>
                            </select>
                            <p class="description">Choose between custom content or Google Ads</p>
                        </td>
                    </tr>
                </table>
                
                <!-- Custom Content Settings -->
                <div id="custom-ad-settings" style="display: none;">
                    <h3>Custom Ad Content</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Ad Content</th>
                            <td>
                                <input type="text" name="reveal_ad_content" value="<?php echo esc_attr(get_option('reveal_ad_content', '🎯 Your Amazing Ad Content Here!')); ?>" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Ad Subtitle</th>
                            <td>
                                <input type="text" name="reveal_ad_subtitle" value="<?php echo esc_attr(get_option('reveal_ad_subtitle', 'Click here for exclusive offers')); ?>" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Ad Link URL</th>
                            <td>
                                <input type="url" name="reveal_ad_link" value="<?php echo esc_attr(get_option('reveal_ad_link', '#')); ?>" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Background Color/Gradient</th>
                            <td>
                                <input type="text" name="reveal_ad_bg_color" value="<?php echo esc_attr(get_option('reveal_ad_bg_color', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')); ?>" class="regular-text" />
                                <p class="description">Use CSS color values or gradients</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Google AdSense Settings -->
                <div id="google-adsense-settings" style="display: none;">
                    <h3>Google AdSense Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">AdSense Client ID</th>
                            <td>
                                <input type="text" name="reveal_ad_google_client" value="<?php echo esc_attr(get_option('reveal_ad_google_client', '')); ?>" class="regular-text" placeholder="ca-pub-1234567890123456" />
                                <p class="description">Your Google AdSense client ID (starts with ca-pub-)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Ad Slot ID</th>
                            <td>
                                <input type="text" name="reveal_ad_google_slot" value="<?php echo esc_attr(get_option('reveal_ad_google_slot', '')); ?>" class="regular-text" placeholder="1234567890" />
                                <p class="description">Your ad unit slot ID</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Ad Format</th>
                            <td>
                                <select name="reveal_ad_google_format">
                                    <option value="auto" <?php selected('auto', get_option('reveal_ad_google_format', 'auto')); ?>>Auto</option>
                                    <option value="horizontal" <?php selected('horizontal', get_option('reveal_ad_google_format', 'auto')); ?>>Horizontal</option>
                                    <option value="rectangle" <?php selected('rectangle', get_option('reveal_ad_google_format', 'auto')); ?>>Rectangle</option>
                                    <option value="vertical" <?php selected('vertical', get_option('reveal_ad_google_format', 'auto')); ?>>Vertical</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Responsive Ads</th>
                            <td>
                                <input type="checkbox" name="reveal_ad_google_responsive" value="1" <?php checked(1, get_option('reveal_ad_google_responsive', '1'), true); ?> />
                                <p class="description">Enable responsive ad sizing</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Custom Google Ad Code Settings -->
                <div id="google-custom-settings" style="display: none;">
                    <h3>Custom Google Ad Code</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Ad Code</th>
                            <td>
                                <textarea name="reveal_ad_google_custom_code" rows="10" cols="80" class="large-text"><?php echo esc_textarea(get_option('reveal_ad_google_custom_code', '')); ?></textarea>
                                <p class="description">Paste your complete Google Ad Manager or custom ad code here</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- General Settings -->
                <h3>General Settings</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Ad Height (px)</th>
                        <td>
                            <input type="number" name="reveal_ad_height" value="<?php echo esc_attr(get_option('reveal_ad_height', '200')); ?>" min="100" max="400" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Reveal Delay (ms)</th>
                        <td>
                            <input type="number" name="reveal_ad_delay" value="<?php echo esc_attr(get_option('reveal_ad_delay', '500')); ?>" min="0" max="5000" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Scroll Threshold (px)</th>
                        <td>
                            <input type="number" name="reveal_ad_scroll_threshold" value="<?php echo esc_attr(get_option('reveal_ad_scroll_threshold', '100')); ?>" min="50" max="500" />
                            <p class="description">Pixels scrolled before hiding the ad</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            function toggleAdSettings() {
                const adType = $('#reveal_ad_type').val();
                
                $('#custom-ad-settings').hide();
                $('#google-adsense-settings').hide();
                $('#google-custom-settings').hide();
                
                if (adType === 'custom') {
                    $('#custom-ad-settings').show();
                } else if (adType === 'google_adsense') {
                    $('#google-adsense-settings').show();
                } else if (adType === 'google_custom') {
                    $('#google-custom-settings').show();
                }
            }
            
            // Initial load
            toggleAdSettings();
            
            // On change
            $('#reveal_ad_type').on('change', toggleAdSettings);
        });
        </script>
        
        <style>
        .form-table th {
            width: 200px;
        }
        .form-table td .description {
            margin-top: 5px;
            color: #666;
            font-style: italic;
        }
        h3 {
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        </style>
        <?php
    }
}

// Initialize the plugin
new RevealAdPlugin();
?>