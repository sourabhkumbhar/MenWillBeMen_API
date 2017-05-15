<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
//    public function createUser($email, $device_id) {
//        require_once 'PassHash.php';
//        $response = array();
//
//        // First check if user already existed in db
//        if (!$this->isUserExists($email)) {
//            // Generating password hash
//            $password_hash = PassHash::hash($password);
//
//            // Generating API key
//            $api_key = $this->generateApiKey();
//
//            // insert query
//            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
//            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);
//
//            $result = $stmt->execute();
//
//            $stmt->close();
//
//            // Check for successful insertion
//            if ($result) {
//                // User successfully inserted
//                return USER_CREATED_SUCCESSFULLY;
//            } else {
//                // Failed to create user
//                return USER_CREATE_FAILED;
//            }
//        } else {
//            // User with same email already existed in the db
//            return USER_ALREADY_EXISTED;
//        }
//
//        return $response;
//    }
    
     public function createUser($fname, $lname, $phone, $email, $device_id, $firebase_reg_id) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($email);

            // Generating API key
            $api_key = $this->generateApiKey();

                                   
            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users"
                    . "(fname,lname,phone, email, device_id,password_hash, api_key,firebase_reg_id)"
                    . " values('$fname', '$lname', '$phone', '$email', '$device_id','$password_hash',"
                    . " '$api_key', '$firebase_reg_id')");

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
//        $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = ?");
//        $stmt->bind_param("s", $email);
//        if ($stmt->execute()) {
//            // $user = $stmt->get_result()->fetch_assoc();
//            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
//            $stmt->fetch();
//            $user = array();
//            $user["name"] = $name;
//            $user["email"] = $email;
//            $user["api_key"] = $api_key;
//            $user["status"] = $status;
//            $user["created_at"] = $created_at;
//            $stmt->close();
//            return $user;
//        } else {
//            return NULL;
//        
//        
        $query = "SELECT * FROM users where email = '$email'";
        return self::getDataByQuery($this, $query);
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    public function getPosts($user_id, $category_id, $language_id,
            $latest, $top, $page_no,$limit) {
   
        $offset = ($page_no-1)*$limit;

        

        
                
                
//                "SELECT p.*, c.* FROM posts p, categories c "
//                    . "WHERE p.id_language = $language_id AND "
//                    . "p.id_category = c.id_category AND"
//                    . " status = 1 ORDER BY id_post DESC "
//                    . "LIMIT $offset,$limit ""
//                        . "
        // DashBoard Latest
        if($latest && $category_id == NULL ){
            $query = "SELECT p.*, c.*, "
                    . "( SELECT COUNT(upl.id_post) FROM user_post_likes upl WHERE upl.id_post = p.id_post ) as post_likes_count, "
                    . "(SELECT EXISTS(SELECT upl.id_user FROM user_post_likes upl WHERE upl.id_post = p.id_post AND upl.id_user = $user_id)) as is_liked "
                    . "FROM posts p, categories c "
                    . "WHERE p.id_language = $language_id "
                    . "AND p.id_category = c.id_category AND status = 1 "
                    . "ORDER BY id_post DESC LIMIT $offset,$limit ";
					
					
            return self::getDataByQuery($this, $query);
        }
        // DashBoard TOP
        if($top && $category_id == NULL){
            $query = "SELECT p.*, c.*, "
                     . "( SELECT COUNT(upl.id_post) FROM user_post_likes upl WHERE upl.id_post = p.id_post ) as post_likes_count, "
                    . "(SELECT EXISTS(SELECT upl.id_user FROM user_post_likes upl WHERE upl.id_post = p.id_post AND upl.id_user = $user_id)) as is_liked "
                    . " FROM posts p, categories c "
                    . "WHERE p.id_language = $language_id AND "
                    . "p.id_category = c.id_category AND "
                    . " status = 1 ORDER BY post_likes_count DESC "
                    . "LIMIT $offset, $limit ";
            return self::getDataByQuery($this, $query);
        }
        // Category TOP
        if($latest && $category_id != NULL){
            $query = "SELECT p.*, c.*, 
                     ( SELECT COUNT(upl.id_post) FROM user_post_likes upl WHERE upl.id_post = p.id_post ) as post_likes_count,
                     ( SELECT EXISTS(SELECT upl.id_user FROM user_post_likes upl WHERE upl.id_post = p.id_post AND upl.id_user = $user_id)) as is_liked
                    FROM posts p, categories c
                    WHERE p.id_language = $language_id AND 
                    p.id_category = c.id_category AND 
                    p.id_category = $category_id AND status = 1
                    ORDER BY id_post DESC 
                    LIMIT $offset,$limit";
			
	
            return self::getDataByQuery($this, $query);
        }
        // DashBoard TOP
        if($top && $category_id != NULL){
            $query = "SELECT p.*, c.*, "
                    ."    ( SELECT COUNT(upl.id_post) FROM user_post_likes upl WHERE upl.id_post = p.id_post ) as post_likes_count,"
                    ." ( SELECT EXISTS(SELECT upl.id_user FROM user_post_likes upl WHERE upl.id_post = p.id_post AND upl.id_user = $user_id)) as is_liked"
                    . " FROM posts p, categories c"
                    . " WHERE p.id_language = $language_id AND  
                        p.id_category = c.id_category AND 
			p.id_category = $category_id AND status = 1
                    ORDER BY post_likes_count DESC 
                    LIMIT $offset, $limit";
            return self::getDataByQuery($this, $query);
        }
        
        

    }
    
    
    public static function getDataByQuery($context, $query) {

//        echo $query."\n";
        $stmt = $context->conn->prepare($query);
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
        $records_array = array();

        while ($row = $result->fetch_assoc()) {

            array_push($records_array, $row);
        }


        return $records_array;
    }
    
    
    public static function updateDataByQuery($context, $query) 
    {
        
//        echo $query;
        $stmt = $context->conn->prepare($query);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    
    
        
        
       
//    public function getPosts($tag_id, $author_id, $quote_id, $POFD, $MFP, $RP) {
//        
//          $authorList_array = array();
//        $author_array = array();
//        
//        if($POFD){
//            
//            $POFDID = self::getPOFDID($this);
//            
//
//            $stmtauthor = $this->conn->prepare("SELECT a.* FROM users a, posts p 
//                                                WHERE a.id = p.id_user AND id_post = 
//                                                " . self::getPOFDID($this). "");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['posts'] = array();
//                $author_id = $row_authors['id'];
//
//
//                $authorQuotes = self::getPostByID($this, $POFDID);
//
//
//
//                while ($row_quotes = $authorQuotes->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    $tags = self::getTagsByQuoteID($this, $row_quotes['id_post']);
//
//                    $quotes_array['tags'] = $tags;
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//
//
//                array_push($authorList_array, $author_array);
//
//            }
//
//
//            return $authorList_array;
//        
//
//            
//        }
//        
//        
//        if($MFQ){
//            
//            $QODID = self::getMFQID($this);
//            
//    
//            $stmtauthor = $this->conn->prepare("SELECT a.* 
//													FROM authors a, quotes q
//													WHERE a.author_id = q.author_id
//													AND quote_id = " . self::getQODID($this). "");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//                $authorQuotes = self::getQuoteByID($this, $QODID);
//
//
//
//                while ($row_quotes = $authorQuotes->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    $tags = self::getTagsByQuoteID($this, $row_quotes['quote_id']);
//
//                    $quotes_array['tags'] = $tags;
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//
//
//                array_push($authorList_array, $author_array);
//
//            }
//
//
//            return $authorList_array;
//        
//
//            
//        }
//
//        if($RQ){
//            
//            $QODID = self::getRQID($this);
//            
//    
//            $stmtauthor = $this->conn->prepare("SELECT a.* 
//													FROM authors a, quotes q
//													WHERE a.author_id = q.author_id
//													AND quote_id = " . self::getQODID($this). "");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//                $authorQuotes = self::getQuoteByID($this, $QODID);
//
//
//
//                while ($row_quotes = $authorQuotes->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    $tags = self::getTagsByQuoteID($this, $row_quotes['quote_id']);
//
//                    $quotes_array['tags'] = $tags;
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//
//
//                array_push($authorList_array, $author_array);
//
//            }
//
//
//            return $authorList_array;
//        
//
//            
//        }
//        
//      
//
//        if ($tag_id == 'all' && $author_id == 'all' && $quote_id == 'null') {
//
//            // All authors All Categories
//
//            $stmtAuthor = $this->conn->prepare("SELECT * from authors 
//            		ORDER BY author_name  ASC");
//            $stmtAuthor->execute();
//            $resultAuthor = $stmtAuthor->get_result();
//            $stmtAuthor->close();
//
//
//            while ($row_authors = $resultAuthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//                $authorQuotes = self::getQuotesByAuthor($this, $author_id);
//
//
//
//                while ($row_quotes = $authorQuotes->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    $tags = self::getTagsByQuoteID($this, $row_quotes['quote_id']);
//
//                    $quotes_array['tags'] = $tags;
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//
//
//                array_push($authorList_array, $author_array);
//            }
//
//
//
//
//            return $authorList_array;
//        } else if ($tag_id == 'all' && $author_id != 'all' && $quote_id == 'null') {
//            // Single author All Categories
//
//            $stmtauthor = $this->conn->prepare("SELECT * from authors 
//            			WHERE author_id = " . $author_id . "");
//            $stmtauthor->execute();
//
//            $resultAuthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//            while ($row_authors = $resultAuthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//                $authorQuotes = self::getQuotesByAuthor($this, $author_id);
//
//
//
//                while ($row_quotes = $authorQuotes->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    $tags = self::getTagsByQuoteID($this, $row_quotes['quote_id']);
//                    $quotes_array['tags'] = $tags;
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//
//
//                array_push($authorList_array, $author_array);
//            }
//
//
//            return $authorList_array;
//        } else if ($category_id != 'all' && $author_id != 'all' && $quote_id == 'null') {
//            // Single author Single Category
//
//            $stmtauthor = $this->conn->prepare("SELECT * from authors
//            			WHERE author_id = " . $author_id . "");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//
//
//
//
//                $stmtQuote = $this->conn->prepare(
//                        "SELECT q.* ,c.category_name
//                			FROM quotes q ,categories c
//                			WHERE q.category_id = c.category_id
//                			AND author_id = " . $author_id . " 
//            				AND q.category_id = " . $category_id . "");
//
//                $stmtQuote->execute();
//                $resultQuote = $stmtQuote->get_result();
//                $stmtQuote->close();
//
//
//                while ($row_quotes = $resultQuote->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//                array_push($authorList_array, $author_array);
//            }
//
//
//            return $authorList_array;
//        } else if ($category_id != 'all' && $author_id == 'all' && $quote_id == 'null') {
//            // Single Category All author
//
//            $stmtauthor = $this->conn->prepare("SELECT * from authors");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//
//
//
//
//                $stmtQuote = $this->conn->prepare(
//                        "SELECT q.* ,c.category_name
//                			FROM quotes q ,categories c
//                			WHERE q.category_id = c.category_id
//                			AND author_id = " . $author_id . "
//            				AND q.category_id = " . $category_id . "");
//
//                $stmtQuote->execute();
//                $resultQuote = $stmtQuote->get_result();
//                $stmtQuote->close();
//
//
//                while ($row_quotes = $resultQuote->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//                array_push($authorList_array, $author_array);
//            }
//
//
//            return $authorList_array;
//        } else if ($category_id == 'null' && $author_id == 'null' && $quote_id != 'null') {
//            // Single Category All author
//
//
//
//            $stmtauthor = $this->conn->prepare("SELECT a.* 
//													FROM author a, quotes q
//													WHERE a.author_id = q.author_id
//													AND quote_id = " . $quote_id . "");
//            $stmtauthor->execute();
//
//            $resultauthor = $stmtauthor->get_result();
//            $stmtauthor->close();
//
//
//            while ($row_authors = $resultauthor->fetch_assoc()) {
//                foreach ($row_authors as $key => $value) {
//                    $author_array[$key] = $value;
//                }
//
//                $author_array['quotes'] = array();
//                $author_id = $row_authors['author_id'];
//
//
//
//
//
//
//                $stmtQuote = $this->conn->prepare(
//                        "SELECT q.* ,c.category_name
//                			FROM quotes q ,categories c
//                			WHERE q.category_id = c.category_id
//                			AND author_id = " . $author_id . "
//            				AND quote_id = " . $quote_id . "");
//
//                $stmtQuote->execute();
//                $resultQuote = $stmtQuote->get_result();
//                $stmtQuote->close();
//
//
//                while ($row_quotes = $resultQuote->fetch_assoc()) {
//                    foreach ($row_quotes as $key => $value) {
//                        $quotes_array[$key] = $value;
//                    }
//
//                    array_push($author_array['quotes'], $quotes_array);
//                }
//
//                array_push($authorList_array, $author_array);
//            }
//
//
//            return $authorList_array;
//        }
//    }

    public static function getTagsByQuoteID($context, $quote_id) {


        $tags_array = array();
        $stmt = $context->conn->prepare(
                "SELECT t.* FROM tags t, posts_tags qt 
                                         WHERE t.tag_id = qt.tag_id
                                        AND qt.quote_id = $quote_id");


        $stmt->execute();
        $resultTags = $stmt->get_result();
        $rows = [];
        while ($row = $resultTags->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public static function getQuotesByAuthor($context, $author_id) {


        $stmt = $context->conn->prepare(
                "SELECT q.* 
                			FROM quotes q 
                			WHERE 
                			author_id = " . $author_id . "
                			ORDER BY q.quote_likes_count  DESC");

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function updateWhatsappCount($post_id) {

        $query = "UPDATE posts 
    SET post_whatsapp_count = post_whatsapp_count + 1
    WHERE id_post = ".$post_id."";
        
    return self::updateDataByQuery($this, $query);

    }
	
    public function updateShareCount($post_id) {

        $query = "UPDATE posts 
    SET post_share_count = post_share_count + 1
    WHERE id_post = ".$post_id."";
        
    return self::updateDataByQuery($this, $query);

    }
    
    public function likePost($user_id,$post_id) {

        $query = "INSERT into user_post_likes(id_user,id_post) VALUES ($user_id, $post_id)";
        
    return self::updateDataByQuery($this, $query);

    }
    
    public function unLikePost($user_id,$post_id) {

        $query = "DELETE FROM user_post_likes WHERE id_user = $user_id AND id_post = $post_id";
        
    return self::updateDataByQuery($this, $query);

    }    
    public static function getPostByID($context, $post_id) {


        $stmt = $context->conn->prepare(
                "SELECT p.* 
                			FROM posts p 
                			WHERE 
                			id_post = " . $post_id . "
                			ORDER BY p.post_likes_count  DESC");

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    public function getCategories($language_id) {

        $query = "SELECT * from categories WHERE id_language = $language_id
    				ORDER BY id_category  ASC";
        
        return self::getDataByQuery($this, $query);
    }
	
	    public function getLanguages() {

        $query = "SELECT * from languages 
    				ORDER BY id_language  ASC";
        
        return self::getDataByQuery($this, $query);
    }
       public function getImages() {

//        $query = "SELECT * from settings where name LIKE '%image%'";
                $query = "SELECT * from settings where name NOT LIKE '%color%'";

        return self::getDataByQuery($this, $query);
    } 
   public function getCardColors() {




        $query = "SELECT * from settings WHERE name LIKE  'color%' AND status =1 ";
        return self::getDataByQuery($this, $query);

    }
    
    
    public static function getPOFDID($context) {




        $stmt = $context->conn->prepare("SELECT * from settings WHERE name = 'post_of_the_day' 
    				");
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
        $res = $result->fetch_assoc();

        $quote_id = $res['value'];


        return $quote_id;
    }
    
    public static function getMFQID($context) {




        $stmt = $context->conn->prepare("SELECT * FROM quotes 
            WHERE quote_likes_count = (SELECT MAX(quote_likes_count) FROM quotes)
    				");
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
        $res = $result->fetch_assoc();
        

        $quote_id = $res['quote_id'];


        return $quote_id;
    }
    
    
//   public static function getMFQID($context) {
//
//
//
//
//        $stmt = $context->conn->prepare("SELECT * FROM quotes 
//            WHERE quote_likes_count = (SELECT MAX(quote_likes_count) FROM quotes)
//    				");
//        $stmt->execute();
//
//        $result = $stmt->get_result();
//        $stmt->close();
//        $res = $result->fetch_assoc();
//        
//
//        $quote_id = $res['quote_id'];
//
//
//        return $quote_id;
//    }

   public static function getRQID($context) {




        $stmt = $context->conn->prepare("SELECT * FROM quotes 
                ORDER BY RAND()

    				");
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
        $res = $result->fetch_assoc();
        

        $quote_id = $res['quote_id'];


        return $quote_id;
    }
    

    public function getAuthors($sort) {


        $stmt = null;

        if ($sort == "by_asc") {

            $stmt = $this->conn->prepare("SELECT * from authors
    				ORDER BY author_name  ASC");
        } else if ($sort == "by_likes") {

            $stmt = $this->conn->prepare("SELECT * from authors
    				ORDER BY author_likes_count DESC");
        }

        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();
        $authors_array = array();

        while ($row = $result->fetch_assoc()) {


            foreach ($row as $key => $value) {
                $authors_temp[$key] = $value;
            }

            array_push($authors_array, $authors_temp);
        }


        return $authors_array;
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();

        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
