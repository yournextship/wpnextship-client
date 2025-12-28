# WPNextShip SDK Documentation

This SDK allows you to easily plug your WordPress plugins into the WPNextShip licensing and update system.

## 1. Installation

Copy the `lib` folder into your plugin's directory.

## 2. Initialization

Initialize the SDK in your main plugin file according to your desired menu placement.

### Option A: Top-Level Menu
This will create a dedicated menu item for your plugin settings/licensing.

```php
// wp-content/plugins/my-plugin/my-plugin.php

require_once plugin_dir_path( __FILE__ ) . 'lib/sdk-template/wp-nextship-loader.php';

add_action( 'plugins_loaded', function() {
    WPNextShip_SDK_Manager::init( array(
        'slug'    => 'my-plugin',
        'version' => '1.0.0', // Current plugin version
        'file'    => plugin_basename( __FILE__ ), // e.g., 'my-plugin/my-plugin.php'
    ) );
} );
```

### Option B: Submenu (e.g., Settings)
This will place the "License" page under an existing parent menu, such as Settings (`options-general.php`) or your own custom menu.

```php
// wp-content/plugins/my-plugin/my-plugin.php

require_once plugin_dir_path( __FILE__ ) . 'lib/sdk-template/wp-nextship-loader.php';

add_action( 'plugins_loaded', function() {
    WPNextShip_SDK_Manager::init( array(
        'slug'        => 'my-plugin',
        'version'     => '1.0.0',
        'file'        => plugin_basename( __FILE__ ),
        'parent_slug' => 'options-general.php', // Adds under Settings
    ) );
} );
```

## 3. How It Works

### Licensing
- The SDK creates a "License" page.
- Users enter their License Key and Email.
- Upon activation, the SDK validates the key against your WPNextShip instance.
- Success stores activation status in `_wpnextship_license_{slug}`.

### Updates
- The SDK hooks into WordPress update checks.
- It sends the current version and license key to your API.
- If a new version is available, it injects the update package into the WordPress updater.
- Regular WordPress update flows work seamlessly.

## 4. File Structure

- `wp-nextship-loader.php`: Main entry point.
- `src/Licensing.php`: Handles menu creation and activation UI.
- `src/Updater.php`: Handles `pre_set_site_transient_update_plugins` filter.

## 5. Troubleshooting

- **Check Logs:** Ensure your API URL is reachable from the site.
- **License Key:** Updates only work with an active license.
- **Slug Match:** Ensure the `slug` passed to `init` matches the product slug in your WPNextShip dashboard.
