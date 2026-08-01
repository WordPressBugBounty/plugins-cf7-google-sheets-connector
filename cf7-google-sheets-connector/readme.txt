=== GSheetConnector – Contact Form 7 Google Sheets Connector & Save CF7 Entries to Database ===
Contributors: westerndeal, abdullah17
Tags: cf7, contact form 7, google sheets, google sheets integration, form submissions
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 5.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send Contact Form 7 submissions to Google Sheets automatically in real time and keep every form entry organized and easy to access.

== Description ==

[CF7 Google Sheets Connector](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) connects [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) to [Google Sheets](https://www.google.com/sheets/about/) and automatically sends form submissions to a Google Sheet in real time. Every new entry, including leads, inquiries, RSVPs, orders, and other form data, is added directly to your spreadsheet. This makes it easy to keep your Contact Form 7 entries organized, searchable, and accessible to your team without relying only on email notifications.

It's a lightweight CF7 Google Sheets integration built for site owners who want a reliable way to connect Contact Form 7 to Google Sheets. With one-click Google OAuth 2.0 authentication, you can quickly connect your Google account, map your CF7 form to a Google Sheet, and start sending form entries automatically.

[Homepage](https://www.gsheetconnector.com/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) | [Documentation](https://www.gsheetconnector.com/docs/cf7-gsheetconnector/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) | [Support](https://www.gsheetconnector.com/support/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) | [Live Demo](https://demo.gsheetconnector.com/cf7-google-sheet-connector-pro/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

= Why Connect Contact Form 7 to Google Sheets? =

Connecting Contact Form 7 to Google Sheets gives you a simple way to collect, organize, and share form submissions without manually copying data from emails or WordPress.

* **Real-time Google Sheets sync** – Send Contact Form 7 submissions to your Google Sheet as soon as they are successfully submitted. No manual exports or copy-pasting.
* **Simple setup** – Connect your Google account, map your CF7 form to a spreadsheet once, and future submissions are automatically added as new rows.
* **Keep form entries organized** – Store leads, inquiries, registrations, RSVPs, orders, and other Contact Form 7 data in a structured spreadsheet.
* **Easy access for your team** – Share, search, sort, and filter incoming submissions in Google Sheets without giving team members access to your WordPress dashboard.
* **Local submission backup** – Optionally store Contact Form 7 entries in your WordPress database, independently of the Google Sheets connection.

= ⚡ Key Features =

== Real-Time Contact Form 7 to Google Sheets Sync ==

* Connect any Contact Form 7 form to a specific Google Sheet and worksheet tab using the Sheet Name, Sheet ID, Tab Name, and Tab ID.
* Automatically append a new spreadsheet row after every successful Contact Form 7 submission using the `wpcf7_mail_sent` event.
* Send your Contact Form 7 field values to their corresponding columns in Google Sheets.
* Supports the Contact Form 7 date special mail tag in the free version.
* Built-in formula-injection protection removes leading `=`, `+`, `-`, and `@` characters from submitted values before they are sent to your spreadsheet.
* Newly added rows have inherited formatting cleared automatically to help prevent unwanted cell styling.

== Easy Google OAuth 2.0 Authentication ==

Connect your Google account quickly without having to create your own Google Cloud project.

* **Quick Connect (recommended)** – Connect your Google account in just a few clicks using Google OAuth 2.0 authentication.
* No Google Cloud project or API keys are required when using Quick Connect.
* Once authenticated, configure your Contact Form 7 form and Google Sheet to start receiving submissions.

For advanced setups, GSheetConnector also provides additional authentication options.

== Google Service Account Authentication ==

Connect Google Sheets using a Google Service Account JSON key for server-to-server authentication.

This method does not require an interactive Google login after configuration and can be useful for agencies, managed websites, and automated environments.

[CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) also provides a manual Google authentication option for organizations that prefer to use their own Google Cloud project and OAuth credentials.

== How Field Mapping Works in the Free Version ==

The free version uses your Contact Form 7 field names as column headers in Google Sheets. This creates a direct connection between each CF7 field and its corresponding spreadsheet column.

For example, if your Contact Form 7 form contains:

`[text your-name]`

`[email your-email]`

`[tel your-phone]`

`[textarea your-message]`

Add the corresponding field names to the first row of your Google Sheet:

`your-name | your-email | your-phone | your-message`

Then follow these steps:

1. Create or open the Google Sheet where you want to receive Contact Form 7 submissions.
2. Add your Contact Form 7 field names as column headers in the first row of the spreadsheet.
3. Make sure each Google Sheets column header matches the corresponding CF7 field name.
4. Enter the required Google Sheet and worksheet details in the connector settings.
5. Save your configuration and submit a test entry through your Contact Form 7 form.
6. The submitted values will automatically appear under their matching columns as a new row.

You only need to configure the column headers once. Future Contact Form 7 submissions will automatically be added to the same Google Sheet.

[CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) makes field management easier with automatic sheet and tab detection, custom column ordering, header renaming, field controls, and additional mail tag options.

== CF7 Database – Store Submissions in WordPress ==

Keep a local record of your Contact Form 7 submissions directly inside WordPress.

* Optionally store every CF7 submission in your WordPress database, independently of the Google Sheets connection.
* Browse submissions separately for each Contact Form 7 form.
* Search and paginate stored form entries.
* Mark submissions as read or unread to help keep track of processed entries.
* Select stored entries and resend them to Google Sheets using the bulk **Send to Spreadsheet** action.

Keeping submissions locally gives you an additional record of your form data and makes it easier to resend entries if a Google Sheets sync is interrupted or needs to be retried.

== Error Logs and System Status ==

GSheetConnector includes built-in diagnostic tools to make troubleshooting your Contact Form 7 Google Sheets connection easier.

* A dedicated error log records Google authentication, connection, and API failures.
* Automatic error de-duplication helps prevent the same recurring error from unnecessarily filling the log.
* The System Status page displays useful information about your WordPress, PHP, plugin, and server environment.
* View recent WordPress debug log information to help identify configuration or compatibility problems.

== Manage Connected Forms from Your Dashboard ==

A WordPress Dashboard widget gives you a quick overview of your Contact Form 7 and Google Sheets connections.

* See connected Contact Form 7 forms at a glance.
* View the Google Sheets linked to your forms.
* Quickly identify where your CF7 submissions are being sent.

= ⭐ Unlock More with CF7 Google Sheets Connector PRO =

The free version provides the essential features needed to connect Contact Form 7 to Google Sheets and automatically send form submissions to a spreadsheet.

For websites that need more control over field mapping, authentication, automation, mail tags, and spreadsheet organization, [CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) adds advanced features including:

* **Multiple Google Sheets feeds** – Send submissions from a single Contact Form 7 form to multiple Google Sheets or worksheet tabs.
* **Automatic Google Sheet and tab detection** – Select your Google Sheet and worksheet tab directly instead of manually entering Sheet IDs and Tab IDs.
* **Advanced field mapping** – Choose which Contact Form 7 fields are sent to Google Sheets and control how your form data is organized.
* **Custom column ordering** – Choose the order in which your Contact Form 7 fields appear in Google Sheets.
* **Custom header names** – Rename Google Sheets column headers instead of being limited to the original CF7 field names.
* **Enable or disable individual fields** – Choose exactly which Contact Form 7 fields should or should not be sent to your spreadsheet.
* **All Contact Form 7 special mail tags** – Send additional submission information using Contact Form 7 special mail tags, including date, time, serial number, remote IP address, user agent, page URL, post ID, post title, site title, and more.
* **Custom mail tags** – Send supported custom mail tag values to Google Sheets for more flexible data collection.
* **Mail tag controls** – Enable or disable special and custom mail tags using simple toggle controls, so you can choose exactly which additional information is added to your Google Sheet.
* **Manual Google authentication** – Create and use your own Google Cloud project and OAuth credentials. This is especially useful for Google Workspace organizations that want to configure their OAuth application with an **Internal** audience and restrict authentication to users within their organization.
* **Header and row styling** – Enable spreadsheet styling and choose the formatting you want to apply to the header and data rows in your connected Google Sheet, helping keep your Contact Form 7 entries organized and easier to read.
* **Freeze header row** – Enable the freeze header option to automatically freeze **Row 1** of your connected Google Sheet, keeping your column headings visible while you scroll through Contact Form 7 submissions.
* **Background colors and formatting** – Apply supported formatting options to make spreadsheet data easier to read and manage.
* **Conditional logic** – Create rules that determine when a Contact Form 7 submission should be sent to Google Sheets.
* **Local file attachment storage** – Store files submitted through Contact Form 7 upload fields locally in your WordPress uploads directory under the /uploads/cf7gs/ folder, organized by year. The file URL is automatically added to the corresponding Google Sheets column, making it easy to open and access uploaded attachments directly from your spreadsheet.
* **Google Drive file storage** – Store files submitted through Contact Form 7 upload fields in Google Drive. The Google Drive file link is automatically added to the corresponding Google Sheets column, so you can quickly open and access uploaded attachments directly from your spreadsheet.
* **CSV export** – Export locally stored Contact Form 7 submissions as a CSV file.
* **Role-based permissions** – Control which WordPress user roles can access and manage GSheetConnector features.
* **Priority support** – Get priority assistance from the GSheetConnector support team.

See the complete [Free vs PRO feature comparison](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector#compare) to compare features and choose the version that fits your workflow.

= Free vs PRO: Mail Tags and Field Mapping =

The free and PRO versions both send Contact Form 7 submissions to Google Sheets, but they provide different levels of control over fields and mail tags.

**In the Free version:**

* Add your Contact Form 7 field names manually as column headers in the first row of your Google Sheet.
* The spreadsheet column names should match the field names used in your CF7 form.
* The Contact Form 7 date special mail tag is supported.
* Once configured, new submissions are automatically added under the matching spreadsheet columns.

**In CF7 Google Sheets Connector PRO:**

* Manage Contact Form 7 fields with more flexible field mapping controls.
* Rename and reorder spreadsheet columns.
* Enable or disable individual fields.
* Use all supported Contact Form 7 special mail tags.
* Use supported custom mail tags.
* Enable or disable individual mail tags with toggle controls.
* Automatically detect Google Sheets and worksheet tabs instead of manually entering IDs.

The free version provides a straightforward way to connect Contact Form 7 to Google Sheets, while PRO adds more control over field mapping, mail tags, spreadsheet organization, and advanced workflows.

= 🔌 Compatibility =

* Works with the latest versions of WordPress and [Contact Form 7](https://wordpress.org/plugins/contact-form-7/).
* Connects Contact Form 7 forms directly to [Google Sheets](https://www.google.com/sheets/about/).
* Compatible with Contact Form 7 forms embedded in pages created with popular page builders such as Elementor, Divi, and Avada.
* Supports WordPress Multisite, with each site managing its own Google connection.

= 📚 Documentation and Support =

Need help setting up your Contact Form 7 Google Sheets integration?

Follow the [CF7 Google Sheets Connector documentation](https://www.gsheetconnector.com/docs/cf7-gsheetconnector/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) for step-by-step setup instructions, configuration guides, and troubleshooting information.

If you need additional help, visit [GSheetConnector Support](https://www.gsheetconnector.com/support/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector).

= 🔗 Useful Links =

* [CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Documentation](https://www.gsheetconnector.com/docs/cf7-gsheetconnector/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Live Demo](https://demo.gsheetconnector.com/cf7-google-sheet-connector-pro/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Support](https://www.gsheetconnector.com/support/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [GSheetConnector Homepage](https://www.gsheetconnector.com/?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

== More Google Sheets Integrations by GSheetConnector ==

GSheetConnector also connects other popular WordPress form plugins, page builders, and eCommerce plugins with Google Sheets. Use the built-in Extensions tab to discover more integrations for your WordPress website.

**Contact Forms Google Sheets Integrations**

* [WPForms → Google Sheets Integration](https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Gravity Forms → Google Sheets Integration](https://www.gsheetconnector.com/gravity-forms-google-sheet-connector?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Ninja Forms → Google Sheets Integration](https://www.gsheetconnector.com/ninja-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Forminator → Google Sheets Integration](https://www.gsheetconnector.com/forminator-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Formidable Forms → Google Sheets Integration](https://www.gsheetconnector.com/formidable-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Fluent Forms → Google Sheets Integration](https://www.gsheetconnector.com/fluent-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [JetFormBuilder → Google Sheets Integration](https://www.gsheetconnector.com/jetformbuilder-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

**eCommerce Google Sheets Integrations**

* [WooCommerce → Google Sheets Integration](https://www.gsheetconnector.com/woocommerce-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Easy Digital Downloads → Google Sheets Integration](https://www.gsheetconnector.com/easy-digital-downloads-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

**Page Builder Forms Google Sheets Integrations**

* [Elementor Forms → Google Sheets Integration](https://www.gsheetconnector.com/elementor-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Avada Forms → Google Sheets Integration](https://www.gsheetconnector.com/avada-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Divi Forms → Google Sheets Integration](https://www.gsheetconnector.com/divi-forms-db-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

Each GSheetConnector integration is built for its respective WordPress plugin, making it easier to send form submissions, orders, and other WordPress data to Google Sheets from one familiar ecosystem.


== Installation ==

Follow these steps to connect Contact Form 7 to Google Sheets and start sending form submissions automatically.

= 1. Install and Activate CF7 Google Sheets Connector =

1. In your WordPress dashboard, go to **Plugins → Add New**.
2. Search for **CF7 Google Sheets Connector**.
3. Click **Install Now**, then click **Activate**.
4. Make sure [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) is also installed and activated, as it is required for this plugin.

You can also install the plugin manually by uploading the `cf7-google-sheets-connector` folder to `/wp-content/plugins/` and activating it from the **Plugins** screen in WordPress.

= 2. Connect Your Google Account =

1. In your WordPress dashboard, go to **Contact → Google Sheets → Integration**.
2. Click **Sign in with Google**.
3. Sign in to the Google account you want to use and approve the requested permissions.
4. After successful authentication, your connected Google account will appear in the Integration tab.

The recommended Quick Connect method uses Google OAuth 2.0 authentication and does not require you to create your own Google Cloud project or API credentials.

You can also use the **Google Service Account** authentication method for server-to-server connections.

= 3. Prepare Your Google Sheet =

1. Create a new Google Sheet or open an existing spreadsheet in [Google Sheets](https://sheets.google.com/).
2. Note the **Sheet Name**, **Sheet ID**, **Tab Name**, and **Tab ID**.
3. In the first row of your worksheet, add the column headers that correspond to your Contact Form 7 fields.
4. Add `date` as the first column if you want to store the submission date.

For example, if your Contact Form 7 form contains:

`[text your-name]`

`[email your-email]`

`[text your-subject]`

`[textarea your-message]`

Your Google Sheet header row can be:

`date | your-name | your-email | your-subject | your-message`

Make sure your spreadsheet headers match the Contact Form 7 field names exactly.

Use lowercase field names with hyphens where required, and avoid spaces, leading numbers, underscores, or quotation marks in your column headers.

= 4. Connect Contact Form 7 to Google Sheets =

1. Go to **Contact → Contact Forms** in your WordPress dashboard.
2. Edit the Contact Form 7 form you want to connect.
3. Open the **Google Sheets** tab inside the form settings.
4. Enter the **Sheet Name**, **Sheet ID**, **Tab Name**, and **Tab ID** for your spreadsheet.
5. Save your form settings.
6. Submit a test entry through your Contact Form 7 form.
7. Open your Google Sheet and confirm that the submission appears as a new row.

That's it. New Contact Form 7 submissions will now be sent automatically to your connected Google Sheet.

== Frequently Asked Questions ==

= How do I connect Contact Form 7 to Google Sheets? =

Install and activate CF7 Google Sheets Connector, then go to **Contact → Google Sheets → Integration** and connect your Google account using Google OAuth 2.0.

Next, create or open your Google Sheet, add your Contact Form 7 field names as column headers, and enter the Sheet Name, Sheet ID, Tab Name, and Tab ID in the **Google Sheets** tab of your Contact Form 7 form.

After saving the settings, submit a test form entry. The submission should appear automatically as a new row in your Google Sheet.

= Does Contact Form 7 automatically send submissions to Google Sheets? =

Contact Form 7 does not send submissions to Google Sheets by itself. CF7 Google Sheets Connector adds this integration.

Once your form and spreadsheet are connected, new Contact Form 7 submissions are automatically sent to Google Sheets in real time, so you don't need to manually export or copy form data.

= Do I need to create a Google Cloud project to connect Contact Form 7 to Google Sheets? =

No. The recommended **Quick Connect** method uses Google OAuth 2.0 authentication and lets you connect your Google account without creating your own Google Cloud project or API credentials.

For advanced requirements, [CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) also supports manual Google authentication using your own Google Cloud project and OAuth credentials.

= How do I connect my Google account? =

Go to **Contact → Google Sheets → Integration** and click **Sign in with Google**.

Choose the Google account you want to connect and approve the requested permissions. Once authentication is complete, the connected account will appear in the Integration tab.

No Google Cloud Console setup is required when using the recommended Quick Connect method.

= Can I use a Google Service Account instead of signing in with Google? =

Yes. CF7 Google Sheets Connector supports Google Service Account authentication.

Create a service account and JSON key in Google Cloud, add the JSON key to the Service Account settings in the plugin, and share your target Google Sheet with the service account email address.

This provides a server-to-server connection without requiring an interactive Google login after configuration.

= How do I map Contact Form 7 fields to Google Sheets columns? =

In the free version, add your Contact Form 7 field names as column headers in the first row of your Google Sheet.

For example, if your Contact Form 7 fields are:

`your-name`, `your-email`, `your-phone`, `your-message`

Use the same names as your Google Sheets column headers:

`your-name | your-email | your-phone | your-message`

When someone submits the form, each value is added under its corresponding Google Sheets column.

[CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) provides advanced field mapping, custom header names, column ordering, field controls, and automatic sheet and tab detection.

= Can I send Contact Form 7 special mail tags to Google Sheets? =

Yes, but support differs between the Free and PRO versions.

The free version supports the Contact Form 7 **date special mail tag**.

CF7 Google Sheets Connector PRO supports additional Contact Form 7 special mail tags, including date, time, serial number, remote IP address, user agent, page URL, post information, site information, and more. PRO also supports custom mail tags and provides toggle controls to enable or disable individual mail tags.

= Can I connect multiple Contact Form 7 forms to Google Sheets? =

Yes. You can connect multiple Contact Form 7 forms to Google Sheets. Each CF7 form can have its own Google Sheet and worksheet configuration.

The free version supports one Google Sheets feed per form.

[CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) supports multiple Google Sheets feeds per form, allowing a single Contact Form 7 form to send data to multiple spreadsheet destinations.

= Can Contact Form 7 submissions be saved in WordPress as well as Google Sheets? =

Yes. Enable the **CF7 Database** feature to store Contact Form 7 submissions locally in your WordPress database in addition to sending them to Google Sheets.

Stored entries can be viewed and searched from WordPress, marked as read or unread, and selected entries can be resent to Google Sheets when needed.

= What happens if a Contact Form 7 submission fails to sync with Google Sheets? =

If the **CF7 Database** feature is enabled under **Settings → General Settings**, the submission can still be stored locally in your WordPress database even if the Google Sheets sync is interrupted.

You can review stored entries and resend selected submissions to Google Sheets using the **Send to Spreadsheet** bulk action.

You can also check the plugin's Error Log and System Status information for authentication, API, permission, or configuration errors.

= Why is my Contact Form 7 data not showing in Google Sheets? =

If your Contact Form 7 submissions are not appearing in Google Sheets, check the following:

1. Make sure the correct **Sheet Name**, **Sheet ID**, **Tab Name**, and **Tab ID** are saved in the form's Google Sheets settings.
2. Check that your Google account is still connected under **Contact → Google Sheets → Integration**.
3. Make sure the first-row Google Sheets headers match your Contact Form 7 field names exactly.
4. Check for incorrect spaces, underscores, capitalization, or other differences in your spreadsheet column headers.
5. If you're using a Google Service Account, make sure the spreadsheet has been shared with the service account email address with the required permissions.
6. Check the plugin's **Error Log** and **System Status** for authentication, Google API, or permission errors.

A column header that does not match its corresponding CF7 field name may result in that field's value not appearing in the expected Google Sheets column.

= My Contact Form 7 form submits, but the submit button keeps spinning. Is GSheetConnector causing it? =

Usually, a continuously spinning Contact Form 7 submit button indicates an issue with the form submission process itself, JavaScript, REST API communication, email delivery, or another WordPress configuration.

CF7 Google Sheets Connector processes the Google Sheets integration after the relevant Contact Form 7 submission event. Check Contact Form 7, your browser console, WordPress configuration, and any email or REST API errors when troubleshooting a stuck submission.

= Can I use CF7 Google Sheets Connector with Elementor, Divi, or Avada? =

Yes. If you embed a Contact Form 7 form inside a page created with Elementor, Divi, Avada, or another page builder, CF7 Google Sheets Connector can still send that Contact Form 7 form's submissions to Google Sheets.

The important point is that the embedded form must be a **Contact Form 7 form**.

If you're using the page builder's own native form instead, use the corresponding GSheetConnector integration:

* [Elementor Forms → Google Sheets Integration](https://www.gsheetconnector.com/elementor-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Divi Forms → Google Sheets Integration](https://www.gsheetconnector.com/divi-forms-db-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)
* [Avada Forms → Google Sheets Integration](https://www.gsheetconnector.com/avada-forms-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector)

= Is my Contact Form 7 data sent through GSheetConnector servers? =

Form submission data is sent from your WordPress website to Google's APIs using the configured Google authentication method rather than being used as a separate GSheetConnector-hosted form submission database.

Your WordPress site can also store submissions locally when the CF7 Database feature is enabled.

As with any website handling personal information, you should configure your WordPress site, forms, Google account, spreadsheet permissions, and privacy disclosures according to the data you collect and the privacy requirements that apply to your website.

= Does CF7 Google Sheets Connector protect against spreadsheet formula injection? =

Yes. The plugin includes formula-injection protection for values sent to Google Sheets. Submitted values beginning with characters such as `=`, `+`, `-`, or `@` are handled before being written to the spreadsheet to help prevent submitted content from being interpreted as a spreadsheet formula.

= Does uninstalling CF7 Google Sheets Connector delete my data? =

By default, uninstalling the plugin keeps saved settings so you can reinstall it without having to configure everything again.

If you want stored plugin data and Google connection details removed during uninstall, enable **Delete data on uninstall** under **Settings → General Settings** before uninstalling the plugin.

= Does CF7 Google Sheets Connector support WordPress Multisite? =

Yes. CF7 Google Sheets Connector supports WordPress Multisite. Each site can manage its own Google connection and Contact Form 7 to Google Sheets configuration.

= What's the difference between CF7 Google Sheets Connector Free and PRO? =

The free version provides the core features needed to connect Contact Form 7 to Google Sheets, send form submissions automatically, configure spreadsheet columns, and optionally store submissions locally in WordPress.

[CF7 Google Sheets Connector PRO](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector) adds advanced features such as multiple Google Sheets feeds, automatic sheet and tab detection, advanced field mapping, custom headers and column ordering, all supported special mail tags, custom mail tags, conditional logic, Google Drive integration, spreadsheet formatting options, CSV export, role-based permissions, and manual Google authentication.

See the [Free vs PRO feature comparison](https://www.gsheetconnector.com/cf7-google-sheet-connector-pro?utm_source=wordpress.org&utm_medium=wp-repo&utm_campaign=cf7-google-sheet-connector#compare) for more details.


== Screenshots ==

1. CF7 Google Sheets Connector Dashboard – Check your Google Sheets connection status and access Contact Form 7 integration documentation and helpful resources.
2. Google Sheets Integration Setup – Authenticate your Google account and connect Contact Form 7 with Google Sheets.
3. Contact Form 7 Google Sheet Settings – Configure the Google Sheet name, sheet tab, and required settings for your Contact Form 7 integration.
4. Google Service Account Setup – Configure Google Service Account credentials to connect Contact Form 7 with Google Sheets.
5. Multiple Google Sheets Feeds (Pro) – Create and manage multiple feeds to send Contact Form 7 submissions to different Google Sheets or sheet tabs.
6. Plugin Uninstall Settings – Choose whether to remove GSheetConnector data and saved configuration when uninstalling the plugin.
7. Contact Form 7 Submission Database – View and manage Contact Form 7 form submissions directly from the WordPress dashboard.
8. User Access and Role Permissions (Pro) – Control which WordPress user roles can access and manage GSheetConnector settings.
9. Beta Features and Version Control (Pro) – Access beta features, test new GSheetConnector functionality, and manage available plugin versions.
10. Plugin and System Information – View GSheetConnector, WordPress, PHP, and server environment information for troubleshooting and support.

== Changelog ==

= 5.2.3 (01-08-2026) =
* Fixed: Minor stability improvements and enhanced error logging.

= 5.2.2 (30-07-2026) =
* Added: File uploads submitted via Contact Form 7 are now copied to a local `uploads/cf7gs` folder, protected from directory listing.
* Fixed: Resolved an issue that could cause unexpected output during plugin activation on some environments.

= 5.2.1 (29-07-2026) =
* Added: Database indexes to improve performance on sites with a large number of stored entries.
* Added: Enhanced plugin security by restricting plugin installation to trusted sources.
* Fixed: Date, time, and timezone handling for entries, error logs, and debug logs.
* Fixed: Form submission sync issue on WordPress multisite installations.
* Fixed: Simultaneous form submissions overwriting spreadsheet data.
* Fixed: Google Sheets sync errors are now properly recorded in the error log.
* Fixed: Negative numbers, phone numbers, and social handle values syncing incorrectly.
* Fixed: Spreadsheet settings being reset when duplicating or programmatically saving forms.
* Fixed: Entry list pagination issue.
* Fixed: Google Sheets sync for forms containing more than 26 spreadsheet columns.
* Fixed: Compatibility issues with newer PHP versions.
* Fixed: Unsaved changes warning conflict with WordPress and other plugins.
* Fixed: Minor security and stability improvements.
* Improved: Google Sheets sync performance and overall admin performance.
* Improved: Connected Google account loading in the admin area.
* Improved: Overall code quality and WordPress Coding Standards compliance.

= 5.2.0 (25-07-2026) =
* Added: Updated the README and added the connected feeds table to the dashboard.

= 5.1.7 (22-05-2026) =
* Added: Custom database tables for storing plugin data instead of post meta to improve performance and data management.
* Added: Delete alert message and confirmation popup in Feed Settings.
* Added: Dashboard tab for quick access to plugin information, notices, and settings.
* Fixed: Proper handling and display of error messages when required Google Sheets and Google Drive permissions are not granted in the Integration tab.
* Fixed: Improved handling of invalid OAuth tokens to prevent repeated debug log entries on page refresh.
* Fixed: Security-related issues.
* Fixed: Activate, Install, and Deactivate functionality issues in the Extensions tab.

= 5.1.6 (10-02-2026) =
* Fixed: Fixed Freemius integration issue.

= 5.1.5 (04-02-2026) =
* Fixed: Admin notices not getting closed and showing error.

= 5.1.4 (26-12-2025) =
* Fixed: OAuth authentication failure caused by API request timeout and PHP 8.2 compatibility issues when saving API credentials.
* Fixed: Permission issues with the plugin settings page by switching to the "manage_options" capability.
* Improved: Code compatibility based on WordPress guidelines.

= 5.1.3 (28-11-2025) =
* Updated: Made few performance tweaks to minimize unnecessary API calls and lower the overall quota usage.

= 5.1.2 (14-11-2025) =
* Fixed: Updated the text domain name.
* Fixed: Improved responsive CSS.
* Updated: Updated the header logo and the dashboard widget logo.

= 5.1.1 (13-10-2025) =
* Updated: The plugin name from "Google Sheet Connector for CF7" to "GSheetConnector for CF7".

= 5.1.0 (27-09-2025) =
* Updated: The plugin name from "CF7 Google Sheet Connector" to "Google Sheet Connector for CF7".

= 5.0.22 (18-09-2025) =
* Updated: Readme file updated.
* Fixed: UI improvements.
* Fixed: Debug log display moved to the uploads folder directory.

= 5.0.21 (26-06-2025) =
* Fixed: Compatible with Contact Form 7 version 6.1 (latest version).

= 5.0.20 (22-04-2025) =
* Added: Moved saving of credentials to database for Auto API Integration.

= 5.0.19 (27-01-2025) =
* Fixed: Minor UI changes.

= 5.0.18 (23-01-2025) =
* Fixed: Vulnerabilities issues.

= 5.0.17 (18-12-2024) =
* Added: Freemius integration.
* Added: Showcased the "Manual Method" button on the Integration tab.
* Added: "Requires By" in our plugin and "Required By" in the parent plugin.

= 5.0.16 (29-11-2024) =
* Fixed: Alert displayed while moving to another page after saving settings.
* Fixed: Undefined error while clicking on "Copy to Clipboard" button.

= 5.0.15 (09-08-2024) =
* Fixed: Fix the CSS conflict with the Contact Form 7 plugin.

= 5.0.14 (07-08-2024) =
* Added: Display a notification when authentication expires.

= 5.0.13 (30-07-2024) =
* Fixed: Google hasn't verified this app error.

= 5.0.12 (26-06-2024) =
* Security: Enhanced user input sanitization to prevent malicious code execution in connected Google Sheets.

= 5.0.11 (11-06-2024) =
* Fixed: Not allowing users to enter the administration screen.

= 5.0.10 (07-06-2024) =
* Fixed: Vulnerabilities issues.

= 5.0.9 (09-04-2024) =
* Added: UI changes.

= 5.0.8 (11-03-2024) =
* Added: Links for support and docs.
* Added: Showcasing PRO features in the Google Sheets tab.

= 5.0.7 (11-12-2023) =
* Updated: UI changes for the tag list in the Google Sheet tab under Contact Form settings.
* Fixed: Resolved active-plugins listing issue in the System Status tab.

= 5.0.6 (05-10-2023) =
* Fixed: Resolved debugging view, open and close link systematically.
* Added: Error message for users without Google Drive and Google Sheets permissions.

= 5.0.5 (03-10-2023) =
* Added: Get Code button replaced with the Sign in with Google button.
* Added: Alert for users without Google Drive and Google Sheets permissions during authentication.
* Added: Redesigned System Status and Error Log for improved functionality and user experience.
* Fixed: Vulnerabilities issues.

= 5.0.4 (24-06-2023) =
* Added: A few more minor UI changes.

= 5.0.3 (04-05-2023) =
* Added: Minor UI changes.

= 5.0.2 (27-04-2023) =
* Fixed: Vulnerabilities issues.

= 5.0.1 (23-02-2023) =
* Added: Remove access permission from Google account while deactivating authentication.
* Fixed: Made compatible with the "Smart Grid-Layout Design for Contact Form 7" plugin.

= 5.0.0 (05-08-2022) =
* Updated: API library to latest version 2.12.6 and Guzzle library to version 7.4.3.
* Migrated: OAuth OOB to an alternative method, as it was deprecated from 3rd October 2022.

= 4.9.2 (19-01-2022) =
* Fixed: 'Line Break' on textarea issue resolved.

= 4.9.1 =
* Fixed: Undefined index issue.

= 4.9 =
* Fixed: Issue with incorrect or expired auth code.
* Fixed: Deactivation issue while adding incorrect and expired auth code.
* Fixed: Displaying of error while setting Contact Form initially.

= 4.8 =
* Fixed: Vulnerability issues.
* Added: 'Upgrade to PRO' links.
* Added: Google Sheet link in settings.
* Updated: Google API version to 2.10.1 and Guzzle library to 7.3.0.
* Various: UI changes.

= 4.7 =
* Added: Display authenticated email ID on the Integration page.
* Fixed: Data not getting saved under exact column names.
* Fixed: Composer functions to avoid clashing with other plugins.

= 4.6 =
* Updated: API library to avoid conflicts with "Facebook for WordPress" and other plugins.

= 4.5 =
* Fixed: Saving of incorrect file name to the Google Sheet.

= 4.4 =
* Fixed: Special mail tag issue caused by Contact Form 7 5.2.2.

= 4.3 =
* Fixed: Special mail tag issue caused by Contact Form 7 5.2.1.

= 4.2 =
* Added: Ability for users to deactivate authentication.
* Fixed: Conflicts error.

= 4.1 =
* Fixed: Displaying of single quote sign in front of numeric and date values.

= 4.0 =
* Upgraded: Google APIs Client Library to version v4.
* Added: Support for capital letters, underscores, numbers, and spaces in CF7 input field names and header names.
* Fixed: Addition of backslash in front of apostrophes and quotation marks.

= 3.0 =
* Updated: API library.
* Added: Ability to permanently close the Google Sheet Connector Pro notice.

= 2.9 =
* Added: Hide Google Sheet menu and settings based on user role Contact Form 7 edit capabilities.

= 2.8 =
* Fixed: Displaying of Google Sheet Connector notice to be dismissible.

= 2.7 =
* Various: UI changes.
* Fixed: No longer deletes authentication data when upgrading to the Pro version.
* Changed: Renamed classes and functions to avoid conflicts when upgrading to the Pro version.

= 2.6 =
* Various: UI changes at the Google Sheet tab under Contact Form settings.

= 2.5 =
* Removed: A prior connection limitation.

= 2.4 =
* Fixed: Connections of Contact Forms with Google Sheet.
* Added: Limitation to connect the first 5 Contact Forms to Google Sheet.

= 2.3.1 =
* Fixed: Images not being displayed.

= 2.3 =
* Fixed: Integration issues.
* Fixed: Functionality issues of limitations from the previous update.

= 2.2 =
* Various: UI changes and bug fixes.
* Added: Limitation for the number of Contact Forms that can connect to Google Sheet.

= 2.1 =
* Added: Google Sheet Connector dashboard widget for quick access to connected forms.
* Added: Option to clear logs.
* Fixed: Multisite plugin activation issue.

= 2.0 (11-06-2018) =
* Fixed: Bypassing of Contact Form 7's built-in data validation.

= 1.9 =
* Fixed: Contact form data not being fetched to Google Sheet.

= 1.8 =
* Fixed: Page hijacking (loading issue) on the front end after submit actions.
* Added: Integrated new special mail tags (including Flamingo serial number) with the spreadsheet without underscores.

= 1.7 (26-08-2017) =
* Added: Integrated special mail tags with the spreadsheet without underscores.
* Fixed: Date format to match WordPress standards.

= 1.6 =
* Updated: Google Spreadsheet library.
* Changed: Class names for the PHP Google Auth library.
* Added: Delete all data on uninstallation of the plugin.

= 1.5 =
* Fixed: Class name conflicts with other plugins.
* Fixed: Issue sending hidden fields via a dynamic-text extension.
* Added: Settings link on plugin activation.

= 1.4 =
* Fixed: 500 Internal Server Error when sheet name or tab name is not set.

= 1.3 =
* Added: .pot file for easier translation.

= 1.2 =
* Updated: Plugin description and related details.

= 1.1 =
* Fixed: Date format and display issues related to non-English dates.
* Fixed: Class name conflict with other plugins.

= 1.0 =
* First public release. Integrated Contact Form 7 with Google Sheets.

== Upgrade Notice ==

= 5.2.1 =
Recommended update. Includes important security, performance, and stability improvements, plus fixes for multisite synchronization and simultaneous submissions. Please back up your site before updating.

= 5.1.7 =
This update introduces new database tables for plugin data, a Dashboard tab, and several stability and security fixes. Back up your site before updating, as recommended for any plugin update.