<?php

namespace OXI_FLIP_BOX_PLUGINS\Classes;

/**
 * Description of Admin_Ajax
 *
 * @author biplo
 */
class Admin_Ajax {

    /**
     * Define $wpdb
     *
     * @since 3.1.0
     */
    public $wpdb;

    /**
     * Database Parent Table
     *
     * @since 3.1.0
     */
    public $parent_table;

    /**
     * Database Import Table
     *
     * @since 3.1.0
     */
    public $import_table;

    /**
     * Database Import Table
     *
     * @since 3.1.0
     */
    public $child_table;

    /**
     * Constructor of plugin class
     *
     * @since 3.1.0
     */
    public function __construct($type = '', $data = '', $styleid = '', $itemid = '') {
        if (!empty($type) && !empty($data)):
            global $wpdb;
            $this->wpdb = $wpdb;
            $this->parent_table = $this->wpdb->prefix . 'oxi_div_style';
            $this->child_table = $this->wpdb->prefix . 'oxi_div_list';
            $this->import_table = $this->wpdb->prefix . 'oxi_div_import';
            $this->$type($data, $styleid, $itemid);
        endif;
    }

    public function array_replace($arr = [], $search = '', $replace = '') {
        array_walk($arr, function (&$v) use ($search, $replace) {
            $v = str_replace($search, $replace, $v);
        });
        return $arr;
    }

    public function create_flip($data = '', $styleid = '', $itemid = '') {
        if (!empty($styleid)):
            $styleid = (int) $styleid;
            $newdata = $this->wpdb->get_row($this->wpdb->prepare('SELECT * FROM ' . $this->parent_table . ' WHERE id = %d ', $styleid), ARRAY_A);
            $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->parent_table} (name, type, style_name, css) VALUES ( %s, %s, %s, %s)", array($data, 'flip', $newdata['style_name'], $newdata['css'])));
            $redirect_id = $this->wpdb->insert_id;
            if ($redirect_id > 0):
                $child = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->child_table WHERE styleid = %d ORDER by id ASC", $styleid), ARRAY_A);
                foreach ($child as $value) {
                    $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->child_table} (styleid, type, files, css) VALUES (%d, %s, %s, %s)", array($redirect_id, 'flip', $value['files'], $value['css'])));
                }
                echo admin_url("admin.php?page=oxi-flip-box-ultimate-new&styleid=$redirect_id");
            endif;
        else:
            $params = json_decode(stripslashes($data), true);
            $newname = $params['name'];
            $rawdata = $params['style'];
            $style = $rawdata['style'];
            $child = $rawdata['child'];
            $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->parent_table} (name, type, style_name, css) VALUES ( %s, %s, %s, %s)", array($newname, 'flip', $style['style_name'], $style['css'])));
            $redirect_id = $this->wpdb->insert_id;
            if ($redirect_id > 0):
                foreach ($child as $value) {
                    $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->child_table} (styleid, type, files, css) VALUES (%d, %s, %s, %s)", array($redirect_id, 'flip', $value['files'], $value['css'])));
                }
                echo admin_url("admin.php?page=oxi-flip-box-ultimate-new&styleid=$redirect_id");
            endif;
        endif;
    }

    public function addons_rearrange($data = '', $styleid = '', $itemid = '') {
        $list = explode(',', $data);
        foreach ($list as $value) {
            $data = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->child_table WHERE id = %d ", $value), ARRAY_A);
            $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->child_table} (styleid, files, css) VALUES (%d, %s, %s)", array($data['styleid'], $data['files'], $data['css'])));
            $redirect_id = $this->wpdb->insert_id;
            if ($redirect_id == 0) {
                return;
            }
            if ($redirect_id != 0) {
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM $this->child_table WHERE id = %d", $value));
            }
        }
        echo 'success';
        return;
    }

    public function shortcode_active($data = '', $styleid = '', $itemid = '') {
        parse_str($data, $params);
        $styleid = (int) $params['oxiimportstyle'];
        if ($styleid):
            $flip = 'flip';
            $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->import_table} (type, name) VALUES (%s, %d)", array($flip, $styleid)));
            echo admin_url("admin.php?page=oxi-flip-box-ultimate-new#Style" . $styleid);
        else:
            echo 'Silence is Golden';
        endif;
    }

    public function shortcode_delete($data = '', $styleid = '', $itemid = '') {
        $styleid = (int) $styleid;
        if ($styleid):
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->parent_table} WHERE id = %d", $styleid));
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->child_table} WHERE styleid = %d", $styleid));
            echo 'done';
        else:
            echo 'Silence is Golden';
        endif;
    }

    public function shortcode_deactive($data = '', $styleid = '', $itemid = '') {
        parse_str($data, $params);
        $styleid = (int) $params['oxideletestyle'];
        if ($styleid):
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->import_table} WHERE name = %d", $styleid));
            echo 'done';
        else:
            echo 'Silence is Golden';
        endif;
    }

    public function shortcode_export($data = '', $styleid = '', $itemid = '') {
        $styleid = (int) $styleid;
        if ($styleid):
            $st = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->parent_table WHERE id = %d", $styleid), ARRAY_A);
            $c = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->child_table WHERE styleid = %d ORDER by id ASC", $styleid), ARRAY_A);
            $style = [
                'id' => $st['id'],
                'type' => ucfirst($st['type']),
                'name' => $st['name'],
                'style_name' => $st['style_name'],
                'css' => htmlentities($st['css'])
            ];
            $child = [];
            foreach ($c as $value) {
                $child[] = [
                    'id' => $value['id'],
                    'styleid' => $value['styleid'],
                    'files' => htmlentities($value['files']),
                    'css' => htmlentities($value['css'])
                ];
            }
            $newdata = ['plugin' => 'flipbox', 'style' => $style, 'child' => $child];
            echo json_encode($newdata);
        else:
            echo 'Silence is Golden';
        endif;
    }

    /**
     * Admin License
     * @return void
     */
    public function oxi_license($data = '', $styleid = '', $itemid = '') {
        $rawdata = json_decode(stripslashes($data), true);
        $new = $rawdata['license'];
        $old = get_option('oxilab_flip_box_license_key');
        $status = get_option('oxilab_flip_box_license_status');
        if ($new == ''):
            if ($old != '' && $status == 'valid'):
                $this->deactivate_license($old);
            endif;
            delete_option('oxilab_flip_box_license_key');
            $data = ['massage' => '<span class="oxi-confirmation-blank"></span>', 'text' => ''];
        else:
            update_option('oxilab_flip_box_license_key', $new);
            delete_option('oxilab_flip_box_license_status');
            $r = $this->activate_license($new);
            if ($r == 'success'):
                $data = ['massage' => '<span class="oxi-confirmation-success"></span>', 'text' => 'Active'];
            else:
                $data = ['massage' => '<span class="oxi-confirmation-failed"></span>', 'text' => $r];
            endif;
        endif;
        echo json_encode($data);
    }

    public function activate_license($key) {
        $api_params = array(
            'edd_action' => 'activate_license',
            'license' => $key,
            'item_name' => urlencode('Flipbox - Image Overlay'),
            'url' => home_url()
        );

        $response = wp_remote_post('https://www.oxilab.org', array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            if (is_wp_error($response)) {
                $message = $response->get_error_message();
            } else {
                $message = __('An error occurred, please try again.');
            }
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if (false === $license_data->success) {

                switch ($license_data->error) {

                    case 'expired' :

                        $message = sprintf(
                                __('Your license key expired on %s.'), date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
                        );
                        break;

                    case 'revoked' :

                        $message = __('Your license key has been disabled.');
                        break;

                    case 'missing' :

                        $message = __('Invalid license.');
                        break;

                    case 'invalid' :
                    case 'site_inactive' :

                        $message = __('Your license is not active for this URL.');
                        break;

                    case 'item_name_mismatch' :

                        $message = sprintf(__('This appears to be an invalid license key for %s.'), OXI_IMAGE_HOVER_TEXTDOMAIN);
                        break;

                    case 'no_activations_left':

                        $message = __('Your license key has reached its activation limit.');
                        break;

                    default :

                        $message = __('An error occurred, please try again.');
                        break;
                }
            }
        }

        if (!empty($message)) {
            return $message;
        }
        update_option('oxilab_flip_box_license_status', $license_data->license);
        return 'success';
    }

    public function deactivate_license($key) {
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license' => $key,
            'item_name' => urlencode('Flipbox - Image Overlay'),
            'url' => home_url()
        );
        $response = wp_remote_post('https://www.oxilab.org', array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {

            if (is_wp_error($response)) {
                $message = $response->get_error_message();
            } else {
                $message = __('An error occurred, please try again.');
            }
            return $message;
        }
        $license_data = json_decode(wp_remote_retrieve_body($response));
        if ($license_data->license == 'deactivated') {
            delete_option('oxilab_flip_box_license_status');
            delete_option('oxilab_flip_box_license_key');
        }
        return 'success';
    }

    /**
     * Admin Settings
     * @return void
     */
    public function oxi_settings($data = '', $styleid = '', $itemid = '') {
        $rawdata = json_decode(stripslashes($data), true);
        update_option($rawdata['name'], $rawdata['value']);
        echo '<span class="oxi-confirmation-success"></span>';
    }

}
