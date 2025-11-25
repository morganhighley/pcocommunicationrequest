# Claude Code: Fix Workflow Status Tracking

## The Problem

The Campaign Management System plugin is trying to use WordPress custom post statuses (`pending_acceptance`, `accepted`, `archived`) to track brief workflow state. However, this doesn't work because:

1. For a brief to be publicly viewable (so ministry leaders can review and accept it via the shareable link), it must have `post_status = 'publish'`
2. A post can only have ONE post_status at a time
3. Therefore, a published brief cannot simultaneously have `post_status = 'pending_acceptance'`

This is why the dashboard shows "0" for Pending Acceptance, Accepted, etc., even though briefs exist—they're all showing as "Published" because that's their actual WordPress post status.

## The Solution

Replace the custom post status approach with a **post meta field** to track workflow state. This allows:
- All publicly viewable briefs to have `post_status = 'publish'` (or `draft` for unpublished)
- Workflow stage tracked separately via `_cms_workflow_status` meta field
- Values: `draft`, `pending_acceptance`, `accepted`, `archived`

## Files to Modify

1. `includes/class-post-type.php` - Remove or deprecate custom post status registration
2. `includes/class-dashboard.php` - Update counting methods to use meta field
3. `includes/class-workflow.php` - Update status transitions to use meta field
4. `includes/class-meta-boxes.php` - Update workflow meta box and column display
5. `templates/dashboard.php` - No changes needed (uses variables from class)
6. `templates/brief-view.php` - Update to read from meta field

---

## Detailed Changes

### 1. `includes/class-post-type.php`

**Remove or comment out the `register_post_status()` method call in the constructor (line 24):**

```php
public function __construct() {
    add_action( 'init', array( $this, 'register_post_type' ) );
    add_action( 'init', array( $this, 'register_taxonomies' ) );
    // REMOVED: add_action( 'init', array( $this, 'register_post_status' ) );
    add_filter( 'template_include', array( $this, 'load_template' ) );
    add_filter( 'single_template', array( $this, 'single_template' ) );
    add_filter( 'wp_insert_post_data', array( $this, 'enable_comments_on_insert' ), 10, 2 );
    add_action( 'save_post_campaign_brief', array( $this, 'ensure_comments_open' ), 10, 2 );
    
    // Set default workflow status for new briefs
    add_action( 'save_post_campaign_brief', array( $this, 'set_default_workflow_status' ), 5, 2 );
}
```

**Add a new method to set default workflow status:**

```php
/**
 * Set default workflow status for new campaign briefs
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Post object.
 */
public function set_default_workflow_status( $post_id, $post ) {
    // Skip if autosave or revision
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Only set if not already set
    $current_status = get_post_meta( $post_id, '_cms_workflow_status', true );
    if ( empty( $current_status ) ) {
        update_post_meta( $post_id, '_cms_workflow_status', 'draft' );
    }
}
```

**You can keep the `register_post_status()` method in the file but don't call it, or remove it entirely.**

---

### 2. `includes/class-dashboard.php`

**Replace the `get_count_by_status()` method (around line 99):**

```php
/**
 * Get count of briefs by workflow status
 *
 * @param string $status Workflow status (draft, pending_acceptance, accepted, archived).
 * @return int
 */
private function get_count_by_status( $status ) {
    global $wpdb;
    
    // Count posts with matching workflow status meta
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID) 
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = %s
        AND p.post_status IN ('publish', 'draft', 'private')
        AND pm.meta_key = '_cms_workflow_status'
        AND pm.meta_value = %s",
        'campaign_brief',
        $status
    ));
    
    return absint( $count );
}
```

**Update `get_recent_briefs` query in `render_dashboard()` method (around line 53) - change the post_status array:**

```php
// Get recent briefs.
$recent_briefs = get_posts(
    array(
        'post_type'      => 'campaign_brief',
        'posts_per_page' => 10,
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'orderby'        => 'modified',
        'order'          => 'DESC',
    )
);
```

**Update the `add_custom_views()` method (around line 148) to filter by meta instead of post status:**

```php
/**
 * Add custom views to post list
 *
 * @param array $views Existing views.
 * @return array
 */
public function add_custom_views( $views ) {
    // Add pending acceptance view.
    $pending_count = $this->get_count_by_status( 'pending_acceptance' );
    $current = isset( $_GET['workflow_status'] ) && 'pending_acceptance' === $_GET['workflow_status'] ? ' class="current"' : '';
    $views['pending_acceptance'] = sprintf(
        '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
        admin_url( 'edit.php?post_type=campaign_brief&workflow_status=pending_acceptance' ),
        $current,
        __( 'Pending Acceptance', 'campaign-mgmt' ),
        $pending_count
    );

    // Add accepted view.
    $accepted_count = $this->get_count_by_status( 'accepted' );
    $current = isset( $_GET['workflow_status'] ) && 'accepted' === $_GET['workflow_status'] ? ' class="current"' : '';
    $views['accepted'] = sprintf(
        '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
        admin_url( 'edit.php?post_type=campaign_brief&workflow_status=accepted' ),
        $current,
        __( 'Accepted', 'campaign-mgmt' ),
        $accepted_count
    );

    // Add archived view.
    $archived_count = $this->get_count_by_status( 'archived' );
    $current = isset( $_GET['workflow_status'] ) && 'archived' === $_GET['workflow_status'] ? ' class="current"' : '';
    $views['archived'] = sprintf(
        '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
        admin_url( 'edit.php?post_type=campaign_brief&workflow_status=archived' ),
        $current,
        __( 'Archived', 'campaign-mgmt' ),
        $archived_count
    );
    
    // Add draft view (workflow draft, not WP draft)
    $draft_count = $this->get_count_by_status( 'draft' );
    $current = isset( $_GET['workflow_status'] ) && 'draft' === $_GET['workflow_status'] ? ' class="current"' : '';
    $views['cms_draft'] = sprintf(
        '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
        admin_url( 'edit.php?post_type=campaign_brief&workflow_status=draft' ),
        $current,
        __( 'Brief Drafts', 'campaign-mgmt' ),
        $draft_count
    );

    return $views;
}
```

**Update `filter_query()` method to handle workflow_status filter:**

```php
/**
 * Filter query based on custom filters
 *
 * @param WP_Query $query Query object.
 */
public function filter_query( $query ) {
    global $pagenow;

    if ( ! is_admin() || 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'campaign_brief' !== $_GET['post_type'] ) {
        return;
    }

    // Workflow status filter
    if ( isset( $_GET['workflow_status'] ) && '' !== $_GET['workflow_status'] ) {
        $meta_query = $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
        $meta_query[] = array(
            'key'   => '_cms_workflow_status',
            'value' => sanitize_text_field( $_GET['workflow_status'] ),
        );
        $query->set( 'meta_query', $meta_query );
        
        // Make sure we're not filtering by WordPress post_status when using workflow filter
        $query->set( 'post_status', array( 'publish', 'draft', 'private' ) );
    }

    // Service level filter.
    if ( isset( $_GET['service_level'] ) && '' !== $_GET['service_level'] ) {
        $tax_query = $query->get( 'tax_query' ) ? $query->get( 'tax_query' ) : array();
        $tax_query[] = array(
            'taxonomy' => 'service_level',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $_GET['service_level'] ),
        );
        $query->set( 'tax_query', $tax_query );
    }

    // Ministry filter.
    if ( isset( $_GET['ministry'] ) && '' !== $_GET['ministry'] ) {
        $tax_query = $query->get( 'tax_query' ) ? $query->get( 'tax_query' ) : array();
        $tax_query[] = array(
            'taxonomy' => 'ministry',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $_GET['ministry'] ),
        );
        $query->set( 'tax_query', $tax_query );
    }
}
```

**Also add a workflow status dropdown filter. Update `add_filters()` method:**

```php
/**
 * Add filter dropdowns to post list
 *
 * @param string $post_type Current post type.
 */
public function add_filters( $post_type ) {
    if ( 'campaign_brief' !== $post_type ) {
        return;
    }

    // Workflow status filter
    $workflow_statuses = array(
        'draft'              => __( 'Draft', 'campaign-mgmt' ),
        'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
        'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
        'archived'           => __( 'Archived', 'campaign-mgmt' ),
    );
    
    $current_workflow = isset( $_GET['workflow_status'] ) ? sanitize_text_field( $_GET['workflow_status'] ) : '';
    echo '<select name="workflow_status">';
    echo '<option value="">' . esc_html__( 'All Workflow Statuses', 'campaign-mgmt' ) . '</option>';
    foreach ( $workflow_statuses as $value => $label ) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $value ),
            selected( $current_workflow, $value, false ),
            esc_html( $label )
        );
    }
    echo '</select>';

    // Service level filter.
    $service_levels = get_terms(
        array(
            'taxonomy'   => 'service_level',
            'hide_empty' => false,
        )
    );

    if ( ! empty( $service_levels ) && ! is_wp_error( $service_levels ) ) {
        $current_level = isset( $_GET['service_level'] ) ? sanitize_text_field( $_GET['service_level'] ) : '';
        echo '<select name="service_level">';
        echo '<option value="">' . esc_html__( 'All Service Levels', 'campaign-mgmt' ) . '</option>';
        foreach ( $service_levels as $level ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $level->slug ),
                selected( $current_level, $level->slug, false ),
                esc_html( $level->name )
            );
        }
        echo '</select>';
    }

    // Ministry filter.
    $ministries = get_terms(
        array(
            'taxonomy'   => 'ministry',
            'hide_empty' => false,
        )
    );

    if ( ! empty( $ministries ) && ! is_wp_error( $ministries ) ) {
        $current_ministry = isset( $_GET['ministry'] ) ? sanitize_text_field( $_GET['ministry'] ) : '';
        echo '<select name="ministry">';
        echo '<option value="">' . esc_html__( 'All Ministries', 'campaign-mgmt' ) . '</option>';
        foreach ( $ministries as $ministry ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $ministry->slug ),
                selected( $current_ministry, $ministry->slug, false ),
                esc_html( $ministry->name )
            );
        }
        echo '</select>';
    }
}
```

---

### 3. `includes/class-workflow.php`

**Update `accept_brief()` method (around line 39) to set meta instead of post status:**

```php
/**
 * Accept brief via AJAX
 */
public function accept_brief() {
    check_ajax_referer( 'cms-public', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $acceptor_name = isset( $_POST['acceptor_name'] ) ? sanitize_text_field( $_POST['acceptor_name'] ) : '';
    $acceptor_email = isset( $_POST['acceptor_email'] ) ? sanitize_email( $_POST['acceptor_email'] ) : '';

    if ( ! $post_id || ! $acceptor_name || ! $acceptor_email ) {
        wp_send_json_error( array( 'message' => __( 'Missing required fields', 'campaign-mgmt' ) ) );
    }

    // Update workflow status via meta field (NOT post_status)
    update_post_meta( $post_id, '_cms_workflow_status', 'accepted' );
    
    // Update acceptance meta fields.
    update_post_meta( $post_id, '_cms_acceptance_status', 'accepted' );
    update_post_meta( $post_id, '_cms_accepted_by', $acceptor_name . ' (' . $acceptor_email . ')' );
    update_post_meta( $post_id, '_cms_accepted_date', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_cms_is_locked', 1 );

    // Send notification to communications team.
    $this->send_acceptance_notification( $post_id, $acceptor_name, $acceptor_email );

    // Try to create Planning Center task.
    $this->create_planning_center_task( $post_id, $acceptor_email );

    wp_send_json_success(
        array(
            'message' => __( 'Brief accepted successfully!', 'campaign-mgmt' ),
        )
    );
}
```

**Update `unlock_brief()` method (around line 80):**

```php
/**
 * Unlock brief via AJAX
 */
public function unlock_brief() {
    check_ajax_referer( 'cms-admin', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied', 'campaign-mgmt' ) ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'campaign-mgmt' ) ) );
    }

    // Unlock the brief.
    update_post_meta( $post_id, '_cms_is_locked', 0 );

    // Clear acceptance metadata so it can be re-accepted after editing.
    delete_post_meta( $post_id, '_cms_acceptance_status' );
    delete_post_meta( $post_id, '_cms_accepted_by' );
    delete_post_meta( $post_id, '_cms_accepted_date' );

    // Change workflow status back to pending_acceptance via meta field
    update_post_meta( $post_id, '_cms_workflow_status', 'pending_acceptance' );

    wp_send_json_success(
        array(
            'message' => __( 'Brief unlocked and acceptance cleared. Status changed to Pending Acceptance. You can now make changes and have the brief re-accepted.', 'campaign-mgmt' ),
        )
    );
}
```

**Update `unaccept_brief()` method (around line 119):**

```php
/**
 * Manually clear acceptance status via AJAX
 */
public function unaccept_brief() {
    check_ajax_referer( 'cms-admin', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied', 'campaign-mgmt' ) ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'campaign-mgmt' ) ) );
    }

    // Clear acceptance metadata.
    delete_post_meta( $post_id, '_cms_acceptance_status' );
    delete_post_meta( $post_id, '_cms_accepted_by' );
    delete_post_meta( $post_id, '_cms_accepted_date' );

    // Change workflow status back to pending_acceptance via meta field
    update_post_meta( $post_id, '_cms_workflow_status', 'pending_acceptance' );

    wp_send_json_success(
        array(
            'message' => __( 'Acceptance status cleared. Brief status changed to Pending Acceptance.', 'campaign-mgmt' ),
        )
    );
}
```

**Update `add_quick_status_change()` method (around line 187) to use meta field:**

```php
/**
 * Add quick status change buttons to edit screen
 */
public function add_quick_status_change() {
    global $post;

    if ( ! $post || 'campaign_brief' !== $post->post_type ) {
        return;
    }
    
    $current_workflow_status = get_post_meta( $post->ID, '_cms_workflow_status', true );
    if ( empty( $current_workflow_status ) ) {
        $current_workflow_status = 'draft';
    }

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add workflow status buttons below publish box
        var currentStatus = '<?php echo esc_js( $current_workflow_status ); ?>';
        var statusButtons = '<div id="cms-workflow-buttons" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
        statusButtons += '<p><strong><?php esc_html_e( 'Workflow Status:', 'campaign-mgmt' ); ?></strong> <span id="cms-current-workflow-status" style="text-transform: capitalize;">' + currentStatus.replace('_', ' ') + '</span></p>';
        statusButtons += '<p style="margin-bottom: 5px;"><strong><?php esc_html_e( 'Change To:', 'campaign-mgmt' ); ?></strong></p>';
        statusButtons += '<button type="button" class="button button-small cms-workflow-btn" data-status="draft"><?php esc_html_e( 'Draft', 'campaign-mgmt' ); ?></button> ';
        statusButtons += '<button type="button" class="button button-small cms-workflow-btn" data-status="pending_acceptance"><?php esc_html_e( 'Pending Acceptance', 'campaign-mgmt' ); ?></button> ';
        statusButtons += '<button type="button" class="button button-small cms-workflow-btn" data-status="archived"><?php esc_html_e( 'Archived', 'campaign-mgmt' ); ?></button>';
        statusButtons += '<input type="hidden" name="cms_workflow_status" id="cms_workflow_status" value="' + currentStatus + '" />';
        statusButtons += '</div>';

        $('#submitdiv .inside').append(statusButtons);

        // Handle workflow status button clicks
        $('.cms-workflow-btn').on('click', function() {
            var status = $(this).data('status');
            $('#cms_workflow_status').val(status);
            $('#cms-current-workflow-status').text(status.replace('_', ' '));
            
            // Highlight the selected button
            $('.cms-workflow-btn').removeClass('button-primary');
            $(this).addClass('button-primary');
        });
        
        // Highlight current status button on load
        $('.cms-workflow-btn[data-status="' + currentStatus + '"]').addClass('button-primary');
    });
    </script>
    <?php
}
```

**Update `check_lock_status()` method (around line 224) to use meta field:**

```php
/**
 * Check if brief should be locked after save
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Post object.
 */
public function check_lock_status( $post_id, $post ) {
    // Get workflow status from meta
    $workflow_status = get_post_meta( $post_id, '_cms_workflow_status', true );
    
    // If workflow status is accepted and not already locked, lock it.
    if ( 'accepted' === $workflow_status ) {
        $is_locked = get_post_meta( $post_id, '_cms_is_locked', true );
        if ( ! $is_locked ) {
            update_post_meta( $post_id, '_cms_is_locked', 1 );
        }
    }

    // If brief was locked and workflow status changed, unlock it
    $is_locked = get_post_meta( $post_id, '_cms_is_locked', true );
    if ( $is_locked && 'accepted' !== $workflow_status ) {
        if ( isset( $_POST['cms_meta_box_nonce'] ) ) {
            update_post_meta( $post_id, '_cms_is_locked', 0 );
        }
    }
}
```

---

### 4. `includes/class-meta-boxes.php`

**Update `render_workflow()` method to use and save the meta field. Find this method and update it:**

```php
/**
 * Render Workflow meta box
 *
 * @param WP_Post $post Current post object.
 */
public function render_workflow( $post ) {
    $workflow_status = get_post_meta( $post->ID, '_cms_workflow_status', true );
    if ( empty( $workflow_status ) ) {
        $workflow_status = 'draft';
    }
    
    $acceptance_status = get_post_meta( $post->ID, '_cms_acceptance_status', true );
    $accepted_by = get_post_meta( $post->ID, '_cms_accepted_by', true );
    $accepted_date = get_post_meta( $post->ID, '_cms_accepted_date', true );
    $is_locked = get_post_meta( $post->ID, '_cms_is_locked', true );
    
    $workflow_labels = array(
        'draft'              => __( 'Draft', 'campaign-mgmt' ),
        'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
        'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
        'archived'           => __( 'Archived', 'campaign-mgmt' ),
    );
    ?>
    <div class="cms-workflow-status">
        <p>
            <strong><?php esc_html_e( 'Workflow Status:', 'campaign-mgmt' ); ?></strong><br>
            <select name="cms_workflow_status" id="cms_workflow_status_select" style="width: 100%;">
                <?php foreach ( $workflow_labels as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $workflow_status, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <?php if ( $accepted_by ) : ?>
            <p class="cms-accepted-info" style="background: #d4edda; padding: 10px; border-radius: 4px;">
                <strong><?php esc_html_e( '✓ Accepted by:', 'campaign-mgmt' ); ?></strong><br>
                <?php echo esc_html( $accepted_by ); ?><br>
                <small><?php echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $accepted_date ) ) ); ?></small>
            </p>
        <?php endif; ?>

        <?php if ( $is_locked ) : ?>
            <p class="cms-locked-notice" style="background: #fff3cd; padding: 10px; border-radius: 4px;">
                <strong><?php esc_html_e( '🔒 Brief is locked', 'campaign-mgmt' ); ?></strong><br>
                <small><?php esc_html_e( 'This brief has been accepted and is locked for editing.', 'campaign-mgmt' ); ?></small>
            </p>
            <p>
                <button type="button" class="button button-secondary" id="cms-unlock-brief">
                    <?php esc_html_e( 'Unlock Brief', 'campaign-mgmt' ); ?>
                </button>
            </p>
        <?php endif; ?>

        <?php if ( $accepted_by && ! $is_locked ) : ?>
            <p>
                <button type="button" class="button button-secondary" id="cms-unaccept-brief">
                    <?php esc_html_e( 'Clear Acceptance', 'campaign-mgmt' ); ?>
                </button>
            </p>
        <?php endif; ?>
    </div>
    <?php
}
```

**Update `save_meta_boxes()` method to save the workflow status meta field. Add this in the save function (around line 516):**

```php
// Save workflow status
if ( isset( $_POST['cms_workflow_status'] ) ) {
    $allowed_statuses = array( 'draft', 'pending_acceptance', 'accepted', 'archived' );
    $new_status = sanitize_text_field( $_POST['cms_workflow_status'] );
    if ( in_array( $new_status, $allowed_statuses, true ) ) {
        update_post_meta( $post_id, '_cms_workflow_status', $new_status );
    }
}
```

**Update the `custom_column_content()` method for the 'status' column to use meta field:**

```php
case 'status':
    $workflow_status = get_post_meta( $post_id, '_cms_workflow_status', true );
    if ( empty( $workflow_status ) ) {
        $workflow_status = 'draft';
    }
    $status_labels = array(
        'draft'              => __( 'Draft', 'campaign-mgmt' ),
        'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
        'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
        'archived'           => __( 'Archived', 'campaign-mgmt' ),
    );
    $status_label = isset( $status_labels[ $workflow_status ] ) ? $status_labels[ $workflow_status ] : ucfirst( $workflow_status );
    
    // Add color coding
    $status_colors = array(
        'draft'              => '#6c757d',
        'pending_acceptance' => '#ffc107',
        'accepted'           => '#28a745',
        'archived'           => '#17a2b8',
    );
    $color = isset( $status_colors[ $workflow_status ] ) ? $status_colors[ $workflow_status ] : '#6c757d';
    
    echo '<span style="background: ' . esc_attr( $color ) . '; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html( $status_label ) . '</span>';
    break;
```

---

### 5. `templates/dashboard.php`

**Update the status display in the Recent Activity table (around line 91-98):**

```php
<?php
$workflow_status = get_post_meta( $brief->ID, '_cms_workflow_status', true );
if ( empty( $workflow_status ) ) {
    $workflow_status = 'draft';
}
$status_labels = array(
    'draft'              => __( 'Draft', 'campaign-mgmt' ),
    'pending_acceptance' => __( 'Pending Acceptance', 'campaign-mgmt' ),
    'accepted'           => __( 'Accepted', 'campaign-mgmt' ),
    'archived'           => __( 'Archived', 'campaign-mgmt' ),
);
$status = isset( $status_labels[ $workflow_status ] ) ? $status_labels[ $workflow_status ] : ucfirst( $workflow_status );
?>
```

---

### 6. `templates/brief-view.php`

**Update the workflow status check (around line 85-88) to use meta field:**

```php
// Workflow status - use meta field instead of post_status
$workflow_status = get_post_meta( get_the_ID(), '_cms_workflow_status', true );
if ( empty( $workflow_status ) ) {
    $workflow_status = 'draft';
}
$acceptance_status = get_post_meta( get_the_ID(), '_cms_acceptance_status', true );
$accepted_by = get_post_meta( get_the_ID(), '_cms_accepted_by', true );
$accepted_date = get_post_meta( get_the_ID(), '_cms_accepted_date', true );
$is_locked = get_post_meta( get_the_ID(), '_cms_is_locked', true );
```

---

## Migration: Update Existing Briefs

After implementing these changes, you'll need to set the workflow status meta for existing briefs. Add this one-time migration function to the main plugin file or run it manually:

```php
/**
 * Migrate existing briefs to use workflow status meta field
 * Run this once after updating the plugin
 */
function cms_migrate_workflow_status() {
    // Check if migration has already run
    if ( get_option( 'cms_workflow_migration_complete' ) ) {
        return;
    }
    
    $briefs = get_posts( array(
        'post_type'      => 'campaign_brief',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ));
    
    foreach ( $briefs as $brief ) {
        // Check if workflow status is already set
        $existing_status = get_post_meta( $brief->ID, '_cms_workflow_status', true );
        if ( ! empty( $existing_status ) ) {
            continue;
        }
        
        // Determine workflow status based on existing data
        $accepted_by = get_post_meta( $brief->ID, '_cms_accepted_by', true );
        $acceptance_status = get_post_meta( $brief->ID, '_cms_acceptance_status', true );
        
        if ( $accepted_by || 'accepted' === $acceptance_status ) {
            $workflow_status = 'accepted';
        } elseif ( 'publish' === $brief->post_status ) {
            // Published briefs without acceptance should be pending acceptance
            $workflow_status = 'pending_acceptance';
        } elseif ( 'draft' === $brief->post_status ) {
            $workflow_status = 'draft';
        } else {
            $workflow_status = 'draft';
        }
        
        update_post_meta( $brief->ID, '_cms_workflow_status', $workflow_status );
    }
    
    // Mark migration as complete
    update_option( 'cms_workflow_migration_complete', true );
}

// Hook to run on admin init (will run once due to option check)
add_action( 'admin_init', 'cms_migrate_workflow_status' );
```

---

## Testing Checklist

1. **Existing brief ("New Awesome Event"):**
   - After migration, should show as "Pending Acceptance" in dashboard
   - Edit the brief and verify you can change workflow status via dropdown or buttons
   - Verify the dashboard counts update correctly

2. **Create a new brief:**
   - Should default to "Draft" workflow status
   - Change to "Pending Acceptance" and verify count updates
   - Accept the brief via public page and verify it shows as "Accepted"

3. **Filter views:**
   - Click "Pending Acceptance" in the admin list views
   - Should filter to show only briefs with that workflow status
   - Test the dropdown filter as well

4. **Dashboard stats:**
   - All four status counts should now work correctly
   - Recent Activity should show correct workflow status (not WordPress post status)
