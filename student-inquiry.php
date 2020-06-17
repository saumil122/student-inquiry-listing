<?php
/*
Plugin Name: Student Listing
Plugin URI: https://github.com/saumil122/student-listing
Description: This plugin is used to manage "student inquiry". A list-table for all inquiries is created for admin using WP_List_Table class.
Version: 1.1
Author: Saumil Nagariya
Author URI: https://github.com/saumil122
License:     GPL2
Custom List Table With Database Example is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Custom List Table With Database Example is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Custom List Table With Database Example. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * $student_inquiry_db_version for current database version
 */
global $student_inquiry_db_version;
$student_inquiry_db_version = '1.1';

/**
 * register_activation_hook implementation
 */
if (!function_exists('sil_student_inquiry_install')) {
    function sil_student_inquiry_install()
    {
        global $wpdb;
        global $student_inquiry_db_version;

        $table_name = $wpdb->prefix . 'inquiries';

        // sql to create your table
        $sql = "CREATE TABLE " . $table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message text,
            course VARCHAR(255),
            amount VARCHAR(255),
            status VARCHAR(10),
            PRIMARY KEY (id)
        );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // save current database version 
        add_option('student_inquiry_db_version', $student_inquiry_db_version);
    }

    register_activation_hook(__FILE__, 'sil_student_inquiry_install');
}


/**
 * register_activation_hook implementation
 */
if (!function_exists('sil_student_inquiry_install_data')) {
    function sil_student_inquiry_install_data()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'inquiries';

        $wpdb->insert($table_name, array(
            'name' => 'Saumil Nagariya',
            'email' => 'saumil@example.com',
            'subject' => 'Inquiry Subject 1',
            'message' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.'
        ));
        $wpdb->insert($table_name, array(
            'name' => 'Saumil N',
            'email' => 'saumil.nagariya@example.com',
            'subject' => 'Inquiry Subject 2',
            'message' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.'
        ));
    }
    register_activation_hook(__FILE__, 'sil_student_inquiry_install_data');
}
/**
 * Trick to update plugin database
 */
if (!function_exists('sil_update_db_check')) {
    function sil_update_db_check()
    {
        global $student_inquiry_db_version;
        if (get_site_option('student_inquiry_db_version') != $student_inquiry_db_version) {
            sil_student_inquiry_install();
        }
    }
    add_action('plugins_loaded', 'sil_update_db_check');
}
/*
* Inquiry listing with CRUD operation
*/

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Student_Inquiry_List_Table extends WP_List_Table
{

    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'Inquiry',
            'plural' => 'Inquiries',
        ));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * render column with actions (edit & delete)
     */
    function column_name($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=inquiries_form&id=%s">%s</a>', $item['id'], esc_html(__('Edit', 'student_inquiry'))),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', sanitize_text_field($_REQUEST['page']), $item['id'], esc_html(__('Delete', 'student_inquiry'))),
        );

        return sprintf(
            '%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    /**
     * Render checkbox column renders
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Render table columns
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => esc_html(__('Name', 'student_inquiry')),
            'email' => esc_html(__('Email', 'student_inquiry')),
            'subject' => esc_html(__('Subject', 'student_inquiry')),
            'message' => esc_html(__('Message', 'student_inquiry')),
            'course' => esc_html(__('Course', 'student_inquiry')),
            'amount' => esc_html(__('Amount', 'student_inquiry')),
            'status' => esc_html(__('Status', 'student_inquiry')),
        );
        return $columns;
    }

    /**
     * Render sortable columns
     */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'email' => array('email', false)
        );
        return $sortable_columns;
    }

    /**
     * bulk actions if has any
     */
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => esc_html(__('Delete', 'student_inquiry'))
        );
        return $actions;
    }

    /**
     * processes bulk actions
     */
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inquiries'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? sanitize_key($_REQUEST['id']) : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    /**
     *prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inquiries';

        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers
        $this->_column_headers = array($columns, $hidden, $sortable);

        // process bulk action if any
        $this->process_bulk_action();

        // pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? sanitize_text_field($_REQUEST['orderby']) : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field($_REQUEST['order']) : 'asc';

        // retrieve items array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

/**
 * Admin/backend page
 */

/**
 * admin_menu hook implementation
 */
if (!function_exists('sil_student_inquiry_admin_menu')) {
    function sil_student_inquiry_admin_menu()
    {
        add_menu_page(__('Inquiries', 'student_inquiry'), __('Inquiries', 'student_inquiry'), 'activate_plugins', 'inquiries', 'sil_page_handler');
        add_submenu_page('inquiries', __('Inquiries', 'student_inquiry'), __('Inquiries', 'student_inquiry'), 'activate_plugins', 'inquiries', 'sil_page_handler');
        // add new will be described in next part
        add_submenu_page('inquiries', __('Add new', 'student_inquiry'), __('Add new', 'student_inquiry'), 'activate_plugins', 'inquiries_form', 'sil_form_page_handler');
        add_submenu_page('inquiries', __('Send email', 'student_inquiry'), __('Send email', 'student_inquiry'), 'activate_plugins', 'inquiries_send_email', 'sil_send_email_page_handler');
    }

    add_action('admin_menu', 'sil_student_inquiry_admin_menu');
}
/**
 * List page handler: Render the table
 * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
 */
if (!function_exists('sil_page_handler')) {
    function sil_page_handler()
    {
        global $wpdb;

        $table = new Student_Inquiry_List_Table();
        $table->prepare_items();

        $message = '';
        if ('delete' === $table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'student_inquiry'), count($_REQUEST['id'])) . '</p></div>';
        }
?>
        <div class="wrap">

            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Inquiries', 'student_inquiry') ?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=inquiries_form'); ?>"><?php _e('Add new', 'student_inquiry') ?></a>
            </h2>
            <?php echo $message; ?>

            <form id="inquiries-table" method="GET">
                <input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>" />
                <?php $table->display() ?>
            </form>

        </div>
    <?php
    }
}
/**
 * Form page handler checks is there some data posted and tries to save it
 * Also it renders basic wrapper in which we are callin meta box render
 */
if (!function_exists('sil_form_page_handler')) {
    function sil_form_page_handler()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inquiries';

        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $default = array(
            'id' => 0,
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
            'course' => '',
            'amount' => '',
            'status' => ''
        );

        // here we are verifying does this request is post back and have correct nonce
        if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $item_valid = sil_validate_inquiry($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if ($result) {
                        $message = esc_html(__('Item added successfully.', 'student_inquiry'));
                    } else {
                        $notice = esc_html(__('There was an error while adding an item.', 'student_inquiry'));
                    }
                } else {
                    $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                    if ($result) {
                        $message = esc_html(__('Item updated successfully.', 'student_inquiry'));
                    } else {
                        $notice = esc_html(__('There was an error while updating an item.', 'student_inquiry'));
                    }
                }
            } else {
                // if $item_valid not true it contains error message(s)
                $notice = $item_valid;
            }
        } else {
            // if this is not post back we load item to edit or give new one to create
            $item = $default;
            if (isset($_REQUEST['id'])) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", sanitize_key($_REQUEST['id'])), ARRAY_A);
                if (!$item) {
                    $item = $default;
                    $notice = esc_html(__('Item is not found.', 'student_inquiry'));
                }
            }
        }

        // here we adding our custom meta box
        add_meta_box('inquiries_form_meta_box', 'Inquiry data', 'sil_form_meta_box_handler', 'inquiry', 'normal', 'default');

    ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Inquiry', 'student_inquiry') ?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=inquiries'); ?>"><?php _e('back to list', 'student_inquiry') ?></a>
            </h2>

            <?php if (!empty($notice)) : ?>
                <div id="notice" class="error">
                    <p><?php echo $notice ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($message)) : ?>
                <div id="message" class="updated">
                    <p><?php echo $message ?></p>
                </div>
            <?php endif; ?>

            <form id="form" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>" />
                <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>" />

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php /* And here we call our custom meta box */ ?>
                            <?php do_meta_boxes('inquiry', 'normal', $item); ?>
                            <input type="submit" value="<?php _e('Save', 'student_inquiry') ?>" id="submit" class="button-primary" name="submit">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php
    }
}
/**
 * renders the custom meta box
 */
if (!function_exists('sil_form_meta_box_handler')) {
    function sil_form_meta_box_handler($item)
    {
    ?>

        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="name"><?php _e('Name', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="name" name="name" type="text" value="<?php echo esc_attr($item['name']) ?>" size="50" class="code" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="email"><?php _e('Email', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="email" name="email" type="email" value="<?php echo esc_attr($item['email']) ?>" size="50" class="code" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="subject"><?php _e('Subject', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="subject" name="subject" type="text" value="<?php echo esc_attr($item['subject']) ?>" size="50" class="code" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="message"><?php _e('Message', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <textarea id="message" name="message" rows="6" class="code" required><?php echo esc_attr($item['message']) ?></textarea>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="course"><?php _e('Course', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="course" name="course" type="text" value="<?php echo esc_attr($item['course']) ?>" size="50" class="code" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="amount"><?php _e('Amount', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="amount" name="amount" type="text" value="<?php echo esc_attr($item['amount']) ?>" size="50" class="code" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="status"><?php _e('Status', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <div style="margin-bottom:15px;"><input type="radio" name="status" id="status_active" value="active" checked />&nbsp; Active</div>
                        <div><input type="radio" name="status" id="status_inactive" value="inactive" />&nbsp; Inactive</div>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php
    }
}
/**
 * validates data 
 */
if (!function_exists('sil_validate_inquiry')) {
    function sil_validate_inquiry($item)
    {
        $messages = array();

        if (empty($item['name'])) $messages[] = esc_html(__('Please provide valid name.', 'student_inquiry'));
        if (!empty($item['email']) && !is_email($item['email'])) $messages[] = esc_html(__('Please provide valid email.', 'student_inquiry'));
        if (empty($item['subject'])) $messages[] = esc_html(__('Please provide valid subject.', 'student_inquiry'));
        if (empty($item['message'])) $messages[] = esc_html(__('Please provide valid message.', 'student_inquiry'));

        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }
}

/***
 *** @sends an email to any user
 ***/
if (!function_exists('sil_send_email')) {
    function sil_send_email($email, $template = null, $from = '', $subject = 'Inquiry regarding class', $args = array())
    {

        //if (!$template) return;

        if (!is_email($email)) return;

        $attachments = null;
        if (isset($from) && $from != '') {
            $headers = 'From: ' . get_option('blogname') . ' <' . $from . '>' . "\r\n";
        } else {
            $headers = 'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>' . "\r\n";
        }
        // HTML e-mail or text
        if (isset($args['email_html']) && $args['email_html'] == 'html') {
            add_filter('wp_mail_content_type', 'sil_set_html_content_type');

            $message = $args['body'];
        } else {
            $message = 'Please set message template for email.';
        }

        // Convert tags in body
        $message = sil_convert_tags($message, $args);

        //echo $message;
        //exit;

        // Send mail
        wp_mail($email, $subject, $message, $headers, $attachments);
    }
}

if (!function_exists('sil_set_html_content_type')) {
    function sil_set_html_content_type()
    {
        return 'text/html';
    }
}
/***
 *** @convert template tags in email template
 ***/
if (!function_exists('sil_convert_tags')) {
    function sil_convert_tags($content, $args = array())
    {

        $search = array(
            '{STUDENT_NAME}',
            '{INVITE_LINK}',
            '{INVITE_PASSWORD}',
            '{ADMIN}',
        );

        $replace = array(
            $args['student_name'],
            $args['invite_link'],
            $args['invite_password'],
            get_option('blogname')
        );

        $content = str_replace($search, $replace, $content);

        return $content;
    }
}
/**
 * render page to send email
 */
if (!function_exists('sil_send_email_page_handler')) {
    function sil_send_email_page_handler()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inquiries';

        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $default = array(
            'from' => '',
            'to' => '',
            'subject' => '',
            'body' => '<p>Hi {STUDENT_NAME},</p><br /><p>Please follow the information for your training session.<br />{INVITE_LINK}<br />{INVITE_PASSWORD}</p><br /><p>Thanks,<br />{ADMIN}</p>',
            'invite_link' => '',
            'invite_password' => '',
            'whatsapp_num' => '',
            'selected_ids' => ''
        );

        // here we are verifying does this request is post back and have correct nonce
        if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);

            if (isset($item['selected_ids']) && $item['selected_ids'] != '') {
                global $wpdb;
                $table_name = $wpdb->prefix . 'inquiries';

                $records = $wpdb->get_results("SELECT * FROM {$table_name} WHERE id in (" . $item['selected_ids'] . ") ", ARRAY_A);
                if (isset($records) && !empty($records) && count($records) > 0) {
                    /*echo '<pre>';
                print_r($item);
                print_r($records);
                echo '</pre>';
                exit;*/
                    foreach ($records as $record) {
                        $args = array(
                            'email_html' => 'html',
                            'email' => $record['email'],
                            'student_name' => $record['name'],
                            'invite_link' => '',
                            'invite_password' => '',
                            'body' => $item['body']
                        );

                        if (isset($item['invite_link']) && $item['invite_link'] != '') {
                            $args['invite_link'] = '<strong>Invite link:</strong> ' . $item['invite_link'];
                        }

                        if (isset($item['invite_password']) && $item['invite_password'] != '') {
                            $args['invite_password'] = '<strong>Invite password:</strong> ' . $item['invite_password'];
                        }

                        if (isset($item['whatsapp_num']) && $item['whatsapp_num'] != '') {
                            $args['invite_link'] = '<strong>Whatsapp number:</strong> ' . $item['whatsapp_num'];
                        }


                        sil_send_email($record['email'], '', $item['from'], $item['subject'], $args);
                    }

                    $message = esc_html(__('Invite send successfully.', 'student_inquiry'));
                } else {
                    $notice = esc_html(__('Oops! something went wrong. Please try again.', 'student_inquiry'));
                }
            } else {
                $notice = esc_html(__('Please select student to send invite.', 'student_inquiry'));
            }
        } else {
            $item = $default;
        }

        // here we adding our custom meta box
        add_meta_box('inquiries_send_meta_box', 'Send invite email', 'sil_send_meta_box_handler', 'inquiry', 'normal', 'default');

    ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Send invite', 'student_inquiry') ?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=inquiries'); ?>"><?php _e('back to list', 'student_inquiry') ?></a>
            </h2>

            <?php if (!empty($notice)) : ?>
                <div id="notice" class="error">
                    <p><?php echo $notice ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($message)) : ?>
                <div id="message" class="updated">
                    <p><?php echo $message ?></p>
                </div>
            <?php endif; ?>

            <form id="form" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>" />
                <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                <input type="hidden" id="selected_ids" name="selected_ids" value="" />

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php /* And here we call our custom meta box */ ?>
                            <?php do_meta_boxes('inquiry', 'normal', $item); ?>
                            <input type="submit" value="<?php _e('Submit', 'student_inquiry') ?>" id="submit" class="button-primary" name="submit">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php
    }
}
/**
 * renders the custom meta box
 */
if (!function_exists('sil_send_meta_box_handler')) {
    function sil_send_meta_box_handler($item)
    {
    ?>
        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="from"><?php _e('From', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="from" name="from" type="email" value="" size="50" class="code">
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="to"><?php _e('to', 'student_inquiry') ?><span style="color:red">*</span></label>
                    </th>
                    <td>
                        <input id="to" name="to" type="text" value="" size="50" class="code">
                        <div class="selected-tags"></div>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="subject"><?php _e('Subject', 'student_inquiry') ?><span style="color:red">*</span></label>
                    </th>
                    <td>
                        <input id="subject" name="subject" type="text" value="" size="50" class="code" required>
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="invite_type"><?php _e('Invite Type', 'student_inquiry') ?><span style="color:red">*</span></label>
                    </th>
                    <td>
                        <select name="invite_type" id="invite_type">
                            <option value="zoom">Zoom</option>
                            <option value="whatsapp">Whatsapp</option>
                            <option value="google">Google Meet</option>
                        </select>
                    </td>
                </tr>
                <tr class="form-field conditional_field zoom google">
                    <th valign="top" scope="row">
                        <label for="invite_link"><?php _e('Invite Link', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="invite_link" name="invite_link" type="text" value="" size="50" class="code" />
                    </td>
                </tr>
                <tr class="form-field conditional_field zoom">
                    <th valign="top" scope="row">
                        <label for="invite_password"><?php _e('Invite Password', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="invite_password" name="invite_password" type="text" value="" size="50" class="code" />
                    </td>
                </tr>
                <tr class="form-field conditional_field whatsapp" style="display:none;">
                    <th valign="top" scope="row">
                        <label for="whatsapp_num"><?php _e('Whatsapp Number', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <input id="whatsapp_num" name="whatsapp_num" type="text" value="" size="50" class="code" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row">
                        <label for="body"><?php _e('Body', 'student_inquiry') ?></label>
                    </th>
                    <td>
                        <div><?php echo $item['body']; ?></div>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}

/* autocomplete for email */
add_action('admin_enqueue_scripts', function () {
    global $student_inquiry_db_version;
    wp_enqueue_style('jquery-auto-complete', plugin_dir_url(__FILE__) . 'css/student-inquiry.css', array(),  $student_inquiry_db_version);
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_script('student-inquiry-js', plugin_dir_url(__FILE__) . 'js/student-inquiry.js', array('suggest'), $student_inquiry_db_version, true);
});

add_action('wp_ajax_student_search', function () {

    $s = wp_unslash($_GET['term']);
    $selected = wp_unslash($_GET['selected']);
    $where_in = '';
    if (isset($selected) && !empty($selected) && count($selected) > 0) {
        $str = implode(",", $selected);
        $where_in = 'and id not in (' . $str . ')';
    }

    $comma = _x(',', 'page delimiter');
    if (',' !== $comma)
        $s = str_replace($comma, ',', $s);
    if (false !== strpos($s, ',')) {
        $s = explode(',', $s);
        $s = $s[count($s) - 1];
    }
    $s = trim($s);

    global $wpdb;
    $table_name = $wpdb->prefix . 'inquiries';


    // To use "LIKE" with the wildcards (%), we have to do some funny business:
    $search = "%{$s}%";
    $where = $wpdb->prepare('WHERE name LIKE %s ' . $where_in, $search);
    $records = $wpdb->get_results("SELECT `id`, `name` as `value` FROM {$table_name} {$where}", ARRAY_A);

    if (isset($records) && !empty($records)) {
        $results = $records;
    } else {
        $results = 'No results';
    }

    echo json_encode($results);
    wp_die();
});

/**
 * Do not forget about translating your plugin, use __('english string', 'your_uniq_plugin_name') to retrieve translated string
 * and _e('english string', 'your_uniq_plugin_name') to echo it
 * in this example plugin your_uniq_plugin_name == student_inquiry
 *
 * to create translation file, use poedit FileNew catalog...
 * Fill name of project, add "." to path (ENSURE that it was added - must be in list)
 * and on last tab add "__" and "_e"
 *
 * Name your file like this: [my_plugin]-[ru_RU].po
 *
 * http://codex.wordpress.org/Writing_a_Plugin#Internationalizing_Your_Plugin
 * http://codex.wordpress.org/I18n_for_WordPress_Developers
 */
if (!function_exists('sil_languages')) {
    function sil_languages()
    {
        load_plugin_textdomain('student_inquiry', false, dirname(plugin_basename(__FILE__)));
    }

    add_action('init', 'sil_languages');
}

/* shortcode for inquiry form */
if (!function_exists('sil_generate_inquiry_form')) {
    function sil_generate_inquiry_form()
    {
        if ($_GET['action'] != 'edit') { ?>

            <style type="text/css">
                .inquiry_form_wrapper {
                    margin: 0 auto;
                    width: 100%;
                    display: block;
                }

                .field_wrapper {
                    width: 100%;
                    margin-bottom: 15px;
                }

                .field_wrapper:after {
                    content: '';
                    display: block;
                    clear: both;
                }

                .field_wrapper label {
                    width: 25%;
                    float: left;
                    margin-bottom: 15px;
                    font-weight: bold;
                }

                .field_wrapper .field {
                    width: 75%;
                    float: right;
                    margin-bottom: 15px;
                }

                .field_wrapper input[type="text"],
                .field_wrapper input[type="email"],
                .field_wrapper select {
                    width: 100%;
                    height: 40px;
                    font-size: 16px;
                    line-height: 40px;
                }

                .field_wrapper textarea {
                    width: 100%;
                    min-height: 100px;
                    font-size: 16px;
                    line-height: 40px;
                }

                #form_msg {
                    margin-bottom: 30px;
                }

                .success {
                    color: green;
                    font-size: 12px;
                    line-height: normal;
                    font-weight: bold;
                }

                .error {
                    color: red;
                    font-size: 12px;
                    line-height: normal;
                }
            </style>

            <div class="inquiry_form_wrapper">
                <form id="inquiry_form" class="inquiry_form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST" enctype="multipart/form-data">
                    <fieldset>
                        <div class="field_wrapper">
                            <label for="inquiry_name"><?php echo esc_html(__('Name', 'student_inquiry')); ?></label>
                            <div class="field">
                                <input name="inquiry_name" id="inquiry_name" type="text" />
                            </div>
                        </div>
                        <div class="field_wrapper">
                            <label for="inquiry_email"><?php echo esc_html(__('Email address', 'student_inquiry')); ?></label>
                            <div class="field">
                                <input name="inquiry_email" id="inquiry_email" type="email" />
                            </div>
                        </div>
                        <div class="field_wrapper">
                            <label for="inquiry_subject"><?php echo esc_html(__('Subject', 'student_inquiry')); ?></label>
                            <div class="field">
                                <input name="inquiry_subject" id="inquiry_subject" type="text" />
                            </div>
                        </div>

                        <div class="field_wrapper">
                            <label for="inquiry_message"><?php echo esc_html(__('Message', 'student_inquiry')); ?></label>
                            <div class="field">
                                <textarea name="inquiry_message" rows="6" id="inquiry_message"></textarea>
                            </div>
                        </div>

                        <div class="field_wrapper">
                            <label>&nbsp;</label>
                            <div class="field">
                                <input type="hidden" name="inquiry_nonce" value="<?php echo wp_create_nonce('inquiry-nonce'); ?>" />
                                <input type="submit" id="inquiry_form_btn" value="<?php echo esc_html(__('Submit', 'student_inquiry')); ?>" />
                            </div>
                        </div>
                    </fieldset>
                </form>
                <div id="form_msg"></div>
            </div>
            <script type="text/javascript">
                function resetvalues() {
                    document.getElementById("inquiry_form").reset();
                }

                jQuery(document).ready(function() {
                    jQuery('#inquiry_form').on('submit', function() {
                        jQuery('#inquiry_form span.error').remove();
                        var fd = new FormData();
                        fd.append("inquiry_name", jQuery('#inquiry_name').val());
                        fd.append("inquiry_email", jQuery('#inquiry_email').val());
                        fd.append("inquiry_subject", jQuery('#inquiry_subject').val());
                        fd.append("inquiry_message", jQuery('#inquiry_message').val());
                        fd.append("action", 'inquiryform_submit');
                        jQuery.ajax({
                            type: 'POST',
                            url: jQuery('#inquiry_form').attr('action'),
                            data: fd,
                            processData: false,
                            contentType: false,
                            dataType: "json",
                            success: function(data, textStatus, XMLHttpRequest) {
                                var id = '#form_msg';
                                jQuery(id).html('');
                                console.log(data);
                                if (data.error) {
                                    jQuery.each(data, function(key, value) {
                                        if (key != 'error') {
                                            jQuery('#' + key).parent().append('<span class="error">' + value + '</span>');
                                        }
                                    });
                                } else {
                                    jQuery(id).append('<p class="success">' + data.success + '</p>');
                                    resetvalues();
                                }
                            },
                            error: function(MLHttpRequest, textStatus, errorThrown) {
                                alert(errorThrown);
                            }

                        });
                        return false;
                    });
                });
            </script>
<?php }
    }
    add_shortcode('INQUIRY_FORM', 'sil_generate_inquiry_form');
}

if (!function_exists('sil_save_data')) {
    function sil_save_data()
    {
        $result = array(
            'error' => false
        );

        if (isset($_POST)) {
            if (isset($_POST['inquiry_name']) && trim($_POST['inquiry_name']) != '') {
                $inquiry_name = sanitize_text_field($_POST['inquiry_name']);
            } else {
                $result['error'] = true;
                $result['inquiry_name'] = 'Please enter valid name.';
            }

            if (isset($_POST['inquiry_email']) && trim($_POST['inquiry_email']) != '') {
                $inquiry_email = sanitize_email($_POST['inquiry_email']);
            } else {
                $result['error'] = true;
                $result['inquiry_email'] = 'Please enter valid email address.';
            }

            if (isset($_POST['inquiry_subject']) && trim($_POST['inquiry_subject']) != '') {
                $inquiry_subject = sanitize_text_field($_POST['inquiry_subject']);
            } else {
                $result['error'] = true;
                $result['inquiry_subject'] = 'Please enter valid subject.';
            }

            if (isset($_POST['inquiry_message']) && trim($_POST['inquiry_message']) != '') {
                $inquiry_message = sanitize_textarea_field($_POST['inquiry_message']);
            } else {
                $result['error'] = true;
                $result['inquiry_message'] = 'Please enter valid message.';
            }

            if ($result['error'] == false) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'inquiries';

                $id = $wpdb->query($wpdb->prepare("INSERT INTO `" . $table_name . "` ( `name`, `email`, `subject`, `message` ) VALUES ( %s, %s, %s, %s )", array($inquiry_name, $inquiry_email, $inquiry_subject, $inquiry_message)));

                $inquiry_id = $wpdb->insert_id;

                //$wpdb->print_error();

                if (isset($inquiry_id) && (int) $inquiry_id > 0) {
                    $result['success'] = 'The inquiry is submitted successfully.';
                } else {
                    $result['error'] = true;
                    $result['error_msg'] = 'Oops! Something went wrong. Please try again.';
                }
            }
        }
        echo json_encode($result);
        wp_die();
    }
    add_action('wp_ajax_inquiryform_submit', 'sil_save_data');
    add_action('wp_ajax_nopriv_inquiryform_submit', 'sil_save_data');
}
