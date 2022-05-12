<?php
if(is_admin())
{
    new Task_Manager_Builder();
}

/**
 * Task_Manager_Builder class will create the page to load the table
 */
class Task_Manager_Builder
{

    /**
     * Menu item will allow us to load the page to display the table
     */
    public static function add_menu_Task_Table_List_page(){
        //Generate Task Admin Page
        add_menu_page( 
            'Task', 
            'Task Manager', 
            'manage_options', 
            'o_task_manager', 
            'Task_Manager_Builder::list_table_page',
            '', 
            30 
        );
        
        //Generate Task Admin Sub Pages
        add_submenu_page( 
            'o_task_manager', 
            'Task', 
            'General', 
            'manage_options', 
            'o_task_manager', 
            'Task_Manager_Builder::list_table_page' 
        );

        add_submenu_page( 
            'o_task_manager', 
            'Task', 
            'Settings', 
            'manage_options', 
            'o_task_manager_setting', 
            'Task_Manager_Builder::settings_page'
        );
    }

    public static function settings_page(){
        ?>
        <h3 class="pt-2">
        <?php _e( 'Configuration Task Manager', 'task' ); ?>
        </h3>
            <?php
            $active_tableau = isset( $_GET[ 'set' ] ) ? $_GET[ 'set' ] : 'o_task_manager_setting';
            ?>
      
        <div class="wrap woocommerce wc_addons_wrap">
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager_setting' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o_task_manager_setting' ? 'nav-tab-active' : ''; ?>"><?php _e( 'TASK', 'task' ); ?></a>
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager_setting&set=o-worklog' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-worklog' ? 'nav-tab-active' : ''; ?>"><?php _e( 'WORKLOG', 'task' ); ?></a>
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager_setting&set=o-evaluation' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-evaluation' ? 'nav-tab-active' : ''; ?>"><?php _e( 'EVALUATION', 'task' ); ?></a>
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager_setting&set=o-active' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-active' ? 'nav-tab-active' : ''; ?>"><?php _e( 'ACTIVATION', 'task' ); ?></a>
            </nav>
            <div class="o_task_manager_setting addons-featured">
        <?php
        if ( $active_tableau == 'o_task_manager_setting' ) {
            taches_tab();
        }
        if ( $active_tableau == 'o-worklog' ) {
            ?>
            <div id="worklog_card">
                <?php worklog_tab(); ?>
            </div>
            <?php
        }
        if ( $active_tableau == 'o-evaluation' ) {
            evaluation_tab();
        }
        if ( $active_tableau == 'o-active' ) {
            active_tab();
        }
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public static function list_table_page()
    {
        $TaskListTable = new Orion_Task_Manager_Table_List();
        $TaskListTable->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Orion Task Manager</h2>
                <?php $TaskListTable->display(); ?>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Orion_Task_Manager_Table_List extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        
        $perPage = 5;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'       => 'Project Title',
            'slug' => 'Slug',
            'project_manager'        => 'Project Manager',
        );

        return $columns;
    }

    public function column_title($item) {
        $actions = array(
                  'edit'      => sprintf('<a href="?page=%s&action=%s&task=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
                  'delete'    => sprintf('<a href="?page=%s&action=%s&task=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
              );
      
        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions) );
    }

    public function process_bulk_action(){

        global $wpdb;
        $table_name = $wpdb->prefix."project"; 
    
            if ('delete' === $this->current_action()) {
    
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) $ids = implode(',', $ids);
    
                if (!empty($ids)) {
                    $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
                }
    
            }
     }

    function get_bulk_actions() {
        $actions = array(
          'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="task[]" value="%s" />', $item['id']
        );    
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        foreach( get_all_project(  ) as $projects ){
            $data_format = (array) $projects;
            $projec_manager = get_userdata( $data_format['project_manager'] )->user_email . ' ('.get_userdata( $data_format['project_manager'] )->display_name .')';
            $project = array_replace( $data_format, array('project_manager' => $projec_manager) ) ;
            $data[] = (array) $project;
        }
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'title':
            case 'slug':
            case 'project_manager':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    public static function get_user_role_(){
	$action = htmlspecialchars($_POST['action']);
	if ($action == 'get_user_role') {
		$user_id = htmlspecialchars($_POST['id_user']);
		if (empty($user_id)) {
			echo '';
		} else {
			$user_info = get_userdata($user_id);
			$user_role = implode(', ', $user_info->roles);
			echo ucfirst($user_role);
		}
	}
	if ($action == 'update_user_role') {
		$user_id = htmlspecialchars($_POST['id_user']);
		$user_new_role = htmlspecialchars($_POST['select_role']);
		$user_id = wp_update_user(array('id' => $user_id, 'role' => $user_new_role));
		echo 'ok';
	}
	if ($action == 'create_template') {
		echo 'template';
	}
	if ($action == 'create_new_projet') {
		echo 'Project';
	}
	wp_die();
}

}
