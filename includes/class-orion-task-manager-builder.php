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
        echo 'Test';
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
            'title'       => 'Title',
            'assigne' => 'Assigne',
            'duedate'        => 'Due Date',
            'status'    => 'Status'
        );

        return $columns;
    }

    public function column_title($item) {
        $actions = array(
                  'edit'      => sprintf('<a href="?page=%s&action=%s&task=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
                  'delete'    => sprintf('<a href="?page=%s&action=%s&task=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
              );
      
        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions) );
    }

    public function process_bulk_action(){

        global $wpdb;
        $table_name = $wpdb->prefix."task"; 
    
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
            '<input type="checkbox" name="task[]" value="%s" />', $item['ID']
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

        $data[] = array(
            'ID'          => 1,
                    'title'       => 'The Shawshank Redemption',
                    'assigne' => 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.',
                    'duedate'        => '1994',
                    'status'    => 'Frank Darabont',
                    );

        $data[] = array(
            'ID'          => 2,
                    'title'       => 'The Godfather',
                    'assigne' => 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.',
                    'duedate'        => '1972',
                    'status'    => 'Francis Ford Coppola',
                    );

        $data[] = array(
            'ID'          => 3,
                    'title'       => 'The Godfather: Part II',
                    'assigne' => 'The early life and career of Vito Corleone in 1920s New York is portrayed while his son, Michael, expands and tightens his grip on his crime syndicate stretching from Lake Tahoe, Nevada to pre-revolution 1958 Cuba.',
                    'duedate'        => '1974',
                    'status'    => 'Francis Ford Coppola',
                    );

        $data[] = array(
            'ID'          => 4,
                    'title'       => 'Pulp Fiction',
                    'assigne' => 'The lives of two mob hit men, a boxer, a gangster\'s wife, and a pair of diner bandits intertwine in four tales of violence and redemption.',
                    'duedate'        => '1994',
                    'status'    => 'Quentin Tarantino',
                    );

        $data[] = array(
            'ID'          => 5,
                    'title'       => 'The Good, the Bad and the Ugly',
                    'assigne' => 'A bounty hunting scam joins two men in an uneasy alliance against a third in a race to find a fortune in gold buried in a remote cemetery.',
                    'duedate'        => '1966',
                    'status'    => 'Sergio Leone',
                    );

        $data[] = array(
            'ID'          => 6,
                    'title'       => 'The Dark Knight',
                    'assigne' => 'When Batman, Gordon and Harvey Dent launch an assault on the mob, they let the clown out of the box, the Joker, bent on turning Gotham on itself and bringing any heroes down to his level.',
                    'duedate'        => '2008',
                    'status'    => 'Christopher Nolan',
                    );

        $data[] = array(
            'ID'          =>7,
                    'title'       => '12 Angry Men',
                    'assigne' => 'A dissenting juror in a murder trial slowly manages to convince the others that the case is not as obviously clear as it seemed in court.',
                    'duedate'        => '1957',
                    'status'    => 'Sidney Lumet',
                    );

        $data[] = array(
            'ID'          => 8,
                    'title'       => 'Schindler\'s List',
                    'assigne' => 'In Poland during World War II, Oskar Schindler gradually becomes concerned for his Jewish workforce after witnessing their persecution by the Nazis.',
                    'duedate'        => '1993',
                    'status'    => 'Steven Spielberg',
                    );

        $data[] = array(
            'ID'          => 9,
                    'title'       => 'The Lord of the Rings: The Return of the King',
                    'assigne' => 'Gandalf and Aragorn lead the World of Men against Sauron\'s army to draw his gaze from Frodo and Sam as they approach Mount Doom with the One Ring.',
                    'duedate'        => '2003',
                    'status'    => 'Peter Jackson',
                    );

        $data[] = array(
            'ID'          => 10,
                    'title'       => 'Fight Club',
                    'assigne' => 'An insomniac office worker looking for a way to change his life crosses paths with a devil-may-care soap maker and they form an underground fight club that evolves into something much, much more...',
                    'duedate'        => '1999',
                    'status'    => 'David Fincher',
                    );

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
            case 'assigne':
            case 'duedate':
            case 'status':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }
}