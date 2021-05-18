<?php
/**
 * Plugin Name: WooReader
 * Description: Show digital content to users who bought the corresponding item in the WooCommerce Webshop
 * Version: 0.3.1
 * Author: Lennert Holvoet
 * Author Uri: https://www.denstylo.be/
 * License: GPLv3 or later
**/
require_once "vendor/autoload.php";
register_activation_hook( __FILE__, 'setup_wooreader');
define( 'WPFP_PATH', plugin_dir_url( __FILE__ ) ); 
define( 'STORING_DIRECTORY', WP_CONTENT_DIR);
$max_upload = min((int)ini_get('post_max_size'), (int)ini_get('upload_max_filesize'));
$max_upload = $max_upload * 1024;
define('MAX_UPLOAD_SIZE', $max_upload);
wp_register_style('bulma-css', WPFP_PATH . '/node_modules/bulma/css/bulma.min.css' );
wp_register_style('dropzone-css', WPFP_PATH . '/js/dropzone/dist/dropzone.css' );
wp_register_style('tree-css', WPFP_PATH . '/css/tree.css' );
wp_register_style('bulma-tagsinput-css', WPFP_PATH . '/node_modules/@creativebulma/bulma-tagsinput/dist/css/bulma-tagsinput.min.css');
wp_enqueue_style('bulma-css');
wp_enqueue_style('dropzone-css');
wp_enqueue_style('tree-css');
wp_enqueue_style('bulma-tagsinput-css');
wp_enqueue_script('dropzone-js', WPFP_PATH . '/js/dropzone/dist/dropzone.js');
wp_enqueue_script('tree-js', WPFP_PATH . '/js/treejs/tree.js');
wp_enqueue_script('bulma-tagsinput-js', WPFP_PATH . '/node_modules/@creativebulma/bulma-tagsinput/dist/js/bulma-tagsinput.min.js');
?>
<?php
function setup_wooreader() {
    //CREATE DB TABLES
    global $wpdb;
    $tables = array(
//        'wooreader_settings' => '',
        'wooreader_documents'  => 
            "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."wooreader_documents` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(1024) DEFAULT NULL,
                `author` varchar(1024) DEFAULT NULL,
                `doctype` varchar(50) DEFAULT NULL,
                `uuid` varchar(36) NOT NULL,
                `mainfile` varchar(50) DEFAULT NULL,
                `coverimage` varchar(50) DEFAULT NULL,
                `published` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uuid` (`uuid`)
                ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;" ,
        'wooreader_woocommerce_link' => 
            "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."wooreader_woocommerce_link` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `wooreader_uuid` varchar(36) NOT NULL DEFAULT '',
                `woocommerce_sku` varchar(128) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
    
    foreach ($tables as $key => $sql) {
        $wpdb->query($sql);
    }
    //mkdir to store files + make inaccesible by browser
    $dir = STORING_DIRECTORY . '/uploads/wooreader/';
    wp_mkdir_p($dir);
    $ht = fopen($dir.'.htaccess','w');
    fwrite($ht, 'deny from all');
    fclose($ht);
}
add_action( 'admin_menu', 'woo_reader_options_page' );

function woo_reader_options_page() {
    add_menu_page(
        'WooReader',
        'WooReader',
        'manage_options',
        'wooreader',
        'woo_reader_document_list',
        'dashicons-book-alt',
        59
    );
    add_submenu_page(
        //'wooreader',
        null ,
        'WooReader Add Document',
        'Add Document',
        'manage_options',
        'wooreader-add',
        'woo_reader_add_document'
    );   
    
    add_submenu_page(
        //'wooreader',
        null ,
        'WooReader Edit Document',
        'Edit Document',
        'manage_options',
        'wooreader-edit',
        'woo_reader_edit_document'
    );

}

function woo_reader_document_list() {
    $q = get_wooreader_document_list();
    if(count($q) < 1) {
        $message = "No documents found. Upload your first document quickly, and start selling!";
    }
    ?>
    <div class="wrap">
      <h1>WooReader</h1>
      <hr />
        <h2><?= $message; ?></h2>
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
            <tr>
                <th colspan="5">
                    <a href="admin.php?page=wooreader-add">
                        <span class="icon-text">
                          <span class="icon">
                            <i class="dashicons dashicons-plus-alt"></i>
                          </span>
                          <span>Add Document</span>
                        </span>
                    </a>
                </th>
            </tr>
            </thead>
            <thead>
            <tr>
                <th colspan="3">Title</th>
                <th>Actions</th>
                <th>Publish</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if(count($q) > 0) {
            foreach ($q as $key => $value) {
                ?>
                <tr>
                    <td colspan="3"><b><?= $value->title; ?></b></td>
                    <td>
                        <a href="admin.php?page=wooreader-edit&uuid=<?= $value->uuid; ?>"> <span class="dashicons dashicons-edit-page" title="edit"></span></a>
                        <a><span class="dashicons dashicons-trash delete-wooreader-document-click" title="delete" id="delete_<?= $value->uuid; ?>"></span></a>
                    </td>
                    <td>
                        <?php 
                            switch($value->published) {
                                case 0:
                                    $publishToggleIcon = 'dashicons-hidden';
                                    $publishToggleTitle = 'publish';
                                break;
                                case 1:
                                    $publishToggleIcon = 'dashicons-visibility';
                                    $publishToggleTitle = 'unpublish';
                                break;
                            }
                        ?>
                        <a><span class="dashicons <?= $publishToggleIcon; ?> publishToggle-wooreader-document-click" title="<?= $publishToggleTitle; ?>" id="togglePublish_<?= $value->uuid; ?>"></span></a>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
        </table>
        <script type="text/javascript" >
            jQuery(document).ready(function($) {
                $('.delete-wooreader-document-click').on('click',function(){
                    console.log(this.id)
                })
                $('.publishToggle-wooreader-document-click').on('click',function(){
                    console.log(this.id)
                    let buttonId = this.id
                    let editId = buttonId.split('_')

                    console.log(editId)
                    console.log(editId[1])
                    let sendData = {
                        action : 'toggle_published_status' ,
                        uuid : editId[1]
                    }
                    $.ajax({
                        url : ajaxurl ,
                        method : 'POST' ,
                        dataType : 'json' ,
                        data : {
                            action : 'toggle_published_status' ,
                            uuid : editId[1]
                        } ,
                        success : function(resp) {
                            console.log(resp)
                            //resp = JSON.parse(resp)
                            if(resp.success == 1) {
                                toggleIcons(buttonId)
                            } 
                        } , 
                        error : function(a,b,c) {
                            console.log(a,b,c)
                        }
                    })
                    
                })
                function toggleIcons(buttonId) {
                    console.log(buttonId)
                    let button = $('#'+buttonId).attr('title')
                    switch(button) {
                        case 'publish' :
                            $('#'+buttonId).attr('title','unpublish')
                            $('#'+buttonId).removeClass('dashicons-visibility').addClass('dashicons-hidden')
                        break;

                        case 'unpublish' :
                            $('#'+buttonId).attr('title','publish')
                            $('#'+buttonId).removeClass('dashicons-hidden').addClass('dashicons-visibility')
                        break;
                    }
                }
            })
        </script>
    </div>
    <?php
}
function woo_reader_add_document() {
    global $wpdb;
    $query = "INSERT INTO " . $wpdb->prefix . "wooreader_documents (`uuid`) VALUES (uuid())";
    $wpdb->prepare($query);
    $wpdb->query($query);
    $last_id = $wpdb->insert_id;

    $getUuid = "SELECT `uuid` FROM " . $wpdb->prefix . "wooreader_documents WHERE `id` = " . $last_id;
    $thisUuid = $wpdb->get_results($getUuid);
    $uuid = $thisUuid[0]->uuid;
    $newFolder = STORING_DIRECTORY . '/uploads/wooreader/' . $uuid . '/root/';
    wp_mkdir_p($newFolder)
    ?>
    <script>
        window.history.replaceState("admin.php?page=wooreader-add","","admin.php?page=wooreader-edit&uuid=<?= $uuid; ?>")
    </script>
    <?php
        woo_reader_edit_document($uuid);
        wp_die();
}

function woo_reader_edit_document($uuid = null) {
    if($uuid == null) {
        $uuid = isset($_GET['uuid']) ? $_GET['uuid'] : null;
        if($uuid === null) {
    ?>
        <script>
        window.history.replaceState("admin.php?page=wooreader-edit","","admin.php?page=wooreader")
        </script>
    <?php
        woo_reader_document_list();
        wp_die();
        }
    }

    $doc = get_wooreader_document($uuid);
    if(isset($doc['error']) && $doc['error'] === 'not_found') {
    ?>
        <script>
        window.history.replaceState("admin.php?page=wooreader-edit","","admin.php?page=wooreader")
        </script>
    <?php
        woo_reader_document_list();
        wp_die();        
    }

?>
    <div class="wrap">
    <h2>Metadata</h2>
       <div class="field">
            <label class="label">Title:</label>
            <div class="control has-icons-left has-icons-right">
            <input class="input" name="title" type="text" placeholder="Title" value="">
            <span class="icon is-small is-left">
                <i class="dashicons dashicons-book-alt"></i>
            </span>
            </div>
        </div>
       <div class="field">
            <label class="label">Author:</label>
            <div class="control has-icons-left has-icons-right">
            <input class="input" name="author" type="text" placeholder="Author" value="">
            <span class="icon is-small is-left">
                <i class="dashicons dashicons-businesswoman"></i>
            </span>
            </div>
        </div>
        <div class="field">
            <div class="control">
                <button class="button is-info" id="save-metadata">Submit</button>
                <span id="save-medata-confirm"></span>
            </div>
        </div>
        <hr />

    <h1>Files</h1>
    <div class="columns" style="height:350px;">
        <div class="panel is-info column is-one-fifth" style="display: flex; flex-flow: column; height: 100%;">
            <p class="panel-heading" style=" flex: 0 1 auto;">Upload</p>
            <div id="myDropzone" class="dropzone"  style=" flex: 1 1 auto; overflow: auto;"></div> 
            <span class="block" style=" flex: 0 1 40px;">(Max. <?= MAX_UPLOAD_SIZE / 1024 . "MB per file"; ?>)</span>
        </div>
        <div class="panel is-info column is-two-fifth" style="display: flex; flex-flow: column; height: 100%;">
            <p class="panel-heading" style=" flex: 0 1 auto;">Folders</p>
            <div id="folder-pane" style=" flex: 1 1 auto; overflow: auto;">
            </div>
        </div>
        <div class="panel is-info column is-two-fifth" style="display: flex; flex-flow: column; height: 100%;">
            <p class="panel-heading" style=" flex: 0 1 auto;">Files</p>
            <div id="file-pane" style="flex: 1 1 auto; overflow: auto;">
            </div>
        </div>
        <!--
        <div class="panel is-info column is-one-third" style="display: flex; flex-flow: column; height: 100%;">
            <p class="panel-heading" style=" flex: 0 1 auto;">Info</p>
            <div id="file-pane" style="flex: 1 1 auto; overflow: auto;">
                foo - bar
            </div>
        </div>
        -->

            <!-- AJAX call to get filelist + actions to set MAIN file / select upload folder / delete files-folders -->
    </div>
        <hr />
    <h2>WooCommerce Link</h2>
    <?php
        $pList = get_woocommerce_products();
         foreach ($pList as $key => $value) {
            ?>
<!-- <?= $value->post_title;?> <i>(<?= $value->sku;?>)</i> -->
<?php
         }
    ?>  
    <div class="field">
    <label class="label">Tags</label>
    <div class="control">
        <input id="tags-with-source" class="input" type="text" data-type="tags" placeholder="Choose Tags">
    </div>
    <script type="text/javascript">
        //BulmaTagsInput.attach();
        document.addEventListener('DOMContentLoaded', function() {
        const tagsInput = document.getElementById('tags-with-source');
        new BulmaTagsInput(tagsInput, {
            closeDropdownOnItemSelect: false,
            freeInput: false,
            caseSensitive : false ,
            clearSelectionOnTyping: true,
            source : async function(value) {
                // Value equal input value
                // We can then use it to request data from external API
                return await fetch(ajaxurl + '?action=get_woo_demo&value=' + value)
                    .then(function(response) {
                        return response.json();
                    });
            } ,
            itemText : 'product_name' ,
            itemValue : 'sku'
            
        });
    }, false);
    </script>
</div>


    </div>
    <script>
        let selectedFolder = ''
        let docData = {
            action : 'get_wooreader_document_data' ,
            uuid : '<?= $uuid; ?>'
        }
        loadWooreaderDocumentData(docData)
        function loadWooreaderDocumentData(getWooreaderDocumentData) {
            jQuery.post(ajaxurl , getWooreaderDocumentData , function(wooreaderDocument) {
                let data = JSON.parse(wooreaderDocument)
                jQuery("input[name=title]").val(data.doc.title)
                jQuery("input[name=author]").val(data.doc.author)
            })
        }
        jQuery('#save-metadata').on('click', function() {
            let title = jQuery("input[name=title]").val()
            let author = jQuery("input[name=author]").val()
            //if(title === '' && author === '') {
            //    jQuery("#save-medata-confirm").html('<span class="icon-text"><span class="icon has-text-danger"><i class="dashicons dashicons-no"></i></span><span>No changes detected.</span></span>');
            //    setTimeout(function(){ jQuery('#save-medata-confirm').empty() }, 3000);
            
            //} else {
                jQuery('#save-metadata').attr('disabled')
                console.log(title,author)
                let data = {
                    action : 'save_metadata' ,
                    title : title ,
                    author : author ,
                    uuid : '<?= $uuid; ?>'
                }
                jQuery.post(ajaxurl , data , function(response) {
                    let resp = JSON.parse(response)
                    jQuery('#save-metadata').attr('enabled')   
                    if(resp.succes === true) {
                        jQuery("#save-medata-confirm").html('<span class="icon-text"><span class="icon has-text-success"><i class="dashicons dashicons-yes"></i></span><span>Changes saved.</span></span>');
                        loadWooreaderDocumentData(data)
                        setTimeout(function(){ jQuery('#save-medata-confirm').empty() }, 3000);
                    } else {
                        jQuery("#save-medata-confirm").html('<span class="icon-text"><span class="icon has-text-danger"><i class="dashicons dashicons-no"></i></span><span>Something went wrong</span></span>')
                        loadWooreaderDocumentData(data)
                        setTimeout(function(){ jQuery('#save-medata-confirm').empty() }, 3000);
                    }
                })
            //}
        })

        function getFileList(uuid , callback) {
            jQuery.get(ajaxurl , { action : 'load_file_list' , uuid : uuid} , function(resp) {
                callback(resp)
            } , 'json')
        } 
        function isEmpty(obj) {
            for(var prop in obj) {
                if(obj.hasOwnProperty(prop)) {
                    return false;
                }
            }
            return JSON.stringify(obj) === JSON.stringify({});
        }

        function buildList(object,depth,fullPath = []){
            //let pathArray = fullPath
            let stripes = ''
            let paddingString = 'pl-' + (depth * 2)
            let html = ''
            depth = depth + 1
            jQuery.each(object,function(k,v){
                if(typeof v == 'object') {
                    console.log(k)
                    fullPath.push(k)
                    console.log(fullPath)
                    html += '<a class="panel-block ' + paddingString + ' selectFolder is-active" id="'+fullPath.join('___')+'"><span class="panel-icon"><i class="dashicons dashicons-open-folder" aria-hidden="true"></i></span>'+k+'</a>'
                    html += buildList(v,depth,fullPath)
                } 
            })
            return html
        }

        function buildNewFolderList(o) {
            let html = ''
            let a = objectDeepKeys(o)
            a.sort()
            for(let i = 0 ; i < a.length ; i++) {
                let splitA = a[i].split('___')
                let check = splitA.reduce(index,o)
                if( check instanceof Object || check instanceof Array) {
                    console.log(splitA)
                    let depth = splitA.length - 1
                    let paddingString = 'pl-' + (depth * 2)
                    html += '<a class="panel-block ' + paddingString + ' selectFolder is-active" id="'+a[i]+'"><span class="panel-icon"><i class="dashicons dashicons-open-folder" aria-hidden="true"></i></span>'+splitA.pop()+'</a>'
                }
            }
            return html
        }

        function buildFileList(object,id_string){
            let html = ''
            let imageFormats = [ 'jpg' , 'jpeg' , 'png' , 'gif' ]
            jQuery.each(object,function(k,v){
                if(typeof v == 'string') {
                    let id_append = v.split('.')[0]
                    html += '<a class="panel-block selectFile" id="'+ id_string + '___' +id_append+'"><span class="panel-icon is-pulled-right has-text-warning"><i class="dashicons dashicons-star-empty makeMainFile" title="Make main file"></i></span>'
                    if(imageFormats.includes(v.split('.').pop().toLowerCase()) == true) {
                        html += '<span class="panel-icon is-pulled-right has-text-link"><i class="dashicons dashicons-format-image makeCoverImage" title="Make cover"></i></span>'
                    }
                    html +='<span class="panel-icon is-pulled-right has-text-danger deleteFile" ><i class="dashicons dashicons-trash"></i></span>'+v+'</a>'
                } 
            })
            return html
        }

        function objectDeepKeys(obj){
            return Object.keys(obj).filter(key => obj[key] instanceof Object).map(key => objectDeepKeys(obj[key]).map(k => `${key}___${k}`)).reduce((x, y) => x.concat(y), Object.keys(obj))
        }

        function loadExplorer(uuid,openThisFolder = null) {
            if(openThisFolder == null) {
                openThisFolder = 'root'
            }
            getFileList(uuid , function(data) {
            console.log(data);
            let myObj = data.fileFolderList
            //let ulList = buildList(myObj,0)
            //console.log(objectDeepKeys(myObj))
            //buildNewFolderList(objectDeepKeys(myObj),myObj)
            let ulList = buildNewFolderList(myObj)
            let mainFile = null
            if(data.docData.mainfile !== null) {
                mainFile = (data.docData.mainfile).split('.')[0]
            }
            let coverFile = null
            if(data.docData.coverimage !== null) {
                coverFile = (data.docData.coverimage).split('.')[0]
            }
            jQuery('#folder-pane').html(ulList)
            selectedFolder = openThisFolder
            loadFolderFiles(openThisFolder,myObj.root,'file-pane',mainFile,coverFile)
            jQuery('.selectFolder').on('click' , function() {
                console.log(this.id)
                let objObj = (this.id)
                console.log(myObj)
                //console.log(retObj(myObj, objObj.split('___')))
                let files = objObj.split('___').reduce(index, myObj)
                console.log(files)
                selectedFolder = (this.id)
                loadFolderFiles(this.id,files,'file-pane',mainFile,coverFile)
            })
        })
        }

        function retObj(obj,keys) {
            //console.log(obj,keys)
            for(let i = 0 ; i < keys.length ; i++) {
                let key = keys[i]
                keys.shift()
                //console.log(keys.length)
                //console.log(obj[key])
                if(keys.length > 1) {
                    retObj(obj[key], keys)
                }
                else {
                    return obj[key]
                }
            }
        }

        loadExplorer('<?= $uuid; ?>',null)

        function loadFolderFiles(id,files,filepane,mainFileSelected,coverFileSelected) {
            console.log(mainFileSelected,coverFileSelected)
            let fullId = id
            let folderString = id.split('___').join("&#x200b;/&#x200b;")
            jQuery('.dz-button').html('Upload folders to "' + folderString + '"')
            jQuery('.selectFolder').removeClass('is-active')
            jQuery('#' + fullId + '.selectFolder').addClass('is-active')
            let myFiles = buildFileList(files,fullId)
            console.log(myFiles)
            if(myFiles != "") {
                jQuery('#'+filepane).html(myFiles) 
                if(mainFileSelected !== null) {
                    if('#' + mainFileSelected){
                        jQuery('#' + mainFileSelected + ' .panel-icon .makeMainFile').removeClass('dashicons-star-empty').addClass('dashicons-star-filled').attr('title','Selected Main File')
                    }
                }
                if(coverFileSelected !== null) {
                    if('#' + coverFileSelected) {
                        jQuery('#' + coverFileSelected + ' .panel-icon .makeCoverImage').removeClass('dashicons-format-image').addClass('dashicons-cover-image').attr('title','Selected Cover Image')
                    }
                }
            } else {
                jQuery('#'+filepane).html('<a class="panel-block disabled"><span class="panel-icon"><i class="dashicons dashicons-format-aside" aria-hidden="true"></i></span>No files</a>')
            }

            jQuery('.makeMainFile').on('click',function(){
                let element = this.parentNode.parentNode
                let thisFileId = element.id
                let thisFolderId = thisFileId.split('___')
                let fileName = thisFolderId.pop()
                console.log(thisFolderId)
                thisFolderId = thisFolderId.join('___')
                let text = element.innerText || element.textContent;
                let data = {
                    action : 'update_default_files' ,
                    typeOfFile : 'mainfile' ,
                    path : thisFolderId ,
                    file : text ,
                    uuid : '<?= $uuid; ?>'
                }
                jQuery.post(ajaxurl , data , function(response) { 
                    if(response[0] === true) {
                        jQuery('.makeCoverImage').removeClass('dashicons-cover-image').addClass('dashicons-format-image').attr('title','Make cover')
                        jQuery('#' + thisFileId + ' .panel-icon .makeCoverImage').removeClass('dashicons-format-image').addClass('dashicons-cover-image').attr('title','Selected Cover Image')
                        loadExplorer('<?= $uuid; ?>',thisFolderId)
                    }
                } , 'json')
            })

            jQuery('.makeCoverImage').on('click',function(){
                let element = this.parentNode.parentNode
                let thisFileId = element.id
                let thisFolderId = thisFileId.split('___')
                let fileName = thisFolderId.pop()
                console.log(thisFolderId)
                thisFolderId = thisFolderId.join('___')
                let text = element.innerText || element.textContent;
                let data = {
                    action : 'update_default_files' ,
                    typeOfFile : 'coverimage' ,
                    path : thisFolderId ,
                    file : text ,
                    uuid : '<?= $uuid; ?>'
                }
                jQuery.post(ajaxurl , data , function(response) { 
                    if(response[0] === true) {
                        jQuery('.makeCoverImage').removeClass('dashicons-cover-image').addClass('dashicons-format-image').attr('title','Make cover')
                        jQuery('#' + thisFileId + ' .panel-icon .makeCoverImage').removeClass('dashicons-format-image').addClass('dashicons-cover-image').attr('title','Selected Cover Image')
                        loadExplorer('<?= $uuid; ?>',thisFolderId)
                    }
                } , 'json')
            })
        }

        function index(obj,i) {return obj[i]}

        isLiteralObject = function(a) {
            return (!!a) && (a.constructor === Object);
        };
        isArray = function(a) {
            return (!!a) && (a.constructor === Array);
        };

        Dropzone.autoDiscover = false;
        var myDropzone = new Dropzone("div#myDropzone", { 
            init : function() {
                this.on("sending", function(file,xhr,data) {
                    data.append("uuid" , docData.uuid)
                    data.append("uploadTo" , selectedFolder)
                    data.append("action",'dropzone_upload')
                    if(file.fullPath){
                        data.append("fullPath", file.fullPath);
                        console.log(file.fullPath)
                    } else {
                        data.append("fullPath", file.name);
                        console.log(file.name)
                    }
                    console.log(data)
                })
            } ,
            method : 'post' ,
            paramName : 'wooreader' , 
            url: ajaxurl ,
            removeFiles : true ,
            success : function(file,response) {
                myDropzone.removeFile(file)
            } ,
            queuecomplete : function() {
                console.log(myDropzone.getRejectedFiles()) 
                loadExplorer('<?= $uuid; ?>', selectedFolder)
            }
        });
    </script>
    <?php
}
?>
<?php
add_action( 'wp_ajax_dropzone_upload', 'dropzone_upload' );
add_action( 'wp_ajax_save_metadata', 'save_metadata' );
add_action( 'wp_ajax_get_wooreader_document_data', 'get_wooreader_document_data' );
add_action( 'wp_ajax_toggle_published_status', 'toggle_published_status' );
add_action( 'wp_ajax_load_list_page', 'load_list_page' );
add_action( 'wp_ajax_load_file_list', 'load_file_list' );
add_action( 'wp_ajax_update_default_files', 'update_default_files' );
add_action( 'wp_ajax_get_woo_demo', 'get_woo_demo' );

function get_woo_demo() {
    //echo json_encode(['foo' , 'bar']);
    global $wpdb;
    $query = "SELECT a.id,a.post_title,b.sku FROM `".$wpdb->prefix."posts` a JOIN `".$wpdb->prefix."wc_product_meta_lookup` b ON a.id = b.product_id WHERE a.`post_type` = 'product'";
    $q = $wpdb->get_results($query);
    echo json_encode($q);
    wp_die();
}

function dropzone_upload() {
    $folder = mySplit($_POST['fullPath']);
    array_pop($folder);
    $path = STORING_DIRECTORY . '/uploads/wooreader/' . $_POST['uuid'] . DIRECTORY_SEPARATOR . $_POST['uploadTo'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,$folder);
    //echo json_encode([ $_FILES , $_POST , $path ]);
    //wp_die();
    foreach ($_FILES as $key => $file) {
        if(!is_dir($path)) {
            mkdir($path);
        }
        if(move_uploaded_file($file['tmp_name'], $path . DIRECTORY_SEPARATOR . $file['name'])){
            echo true;
            wp_die();
        }
    }
    echo false;
    wp_die();  // this is required to terminate immediately and return a proper response
}

function mySplit($s) {
    if(strpos($s, '/') !== false) {
        $d = '/';
    }elseif(strpos($s,'\\') !== false) {
        $d = '\\';
    } else {
        $d = '/';
    }
    $ret = explode($d, $s);
    //$ret[0] .= $d;
    return $ret;
}


function load_list_page() {
    ?>
    <script>
        window.history.replaceState("admin.php?page=wooreader-edit","","admin.php?page=wooreader")
    </script>
    <?php
    woo_reader_document_list();
    wp_die();
}

function save_metadata() : string {
    global $wpdb;
    $table = $wpdb->prefix . "wooreader_documents";
    $data = ["author" => $_POST['author'] , "title" => $_POST['title']];
    $dataFormat = ["%s" , "%s"];
    $where = ["uuid" => $_POST['uuid']];
    $whereFormat = ["%s"];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $doUpdate = $wpdb->update($table , $data , $where , $dataFormat , $whereFormat);
    echo json_encode(['succes' => (bool) $doUpdate , 'message' => $wpdb->last_query ] );
    wp_die();
}

function toggle_published_status() {
    global $wpdb;
    $uuid = $_POST['uuid'];
    $table = $wpdb->prefix . "wooreader_documents";
    $query = $wpdb->prepare("UPDATE $table SET `published` = !`published` WHERE `uuid` = %s" , $uuid);
    $ok = $wpdb->query($query);
    echo json_encode(['success' => $ok ]);
    wp_die();
}
function get_wooreader_document_list() : array {
    global $wpdb;
    $q = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wooreader_documents");
    return stripslashes_deep($q);
}
function get_wooreader_document(string $uuid = null) : array {
    global $wpdb;
    $q = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wooreader_documents WHERE `uuid` = '$uuid'");
    if($wpdb->num_rows < 1) {
        return ['error' => 'not_found'];
    }
    //return stripslashes_deep($q);   
    return $q;   
}
function get_woocommerce_products($published = true) : array {
    global $wpdb;
    $query = "SELECT a.id,a.post_title,b.sku FROM `".$wpdb->prefix."posts` a JOIN `".$wpdb->prefix."wc_product_meta_lookup` b ON a.id = b.product_id WHERE a.`post_type` = 'product'";
    $q = $wpdb->get_results($query);
    return stripslashes_deep($q);
}
function get_wooreader_document_data() {
    $uuid = $_POST['uuid'];
    $doc = get_wooreader_document($uuid);
    $title = isset($doc[0]->title) ?  $doc[0]->title : null;
    $author = isset($doc[0]->author) ?  $doc[0]->author : null; 
    $doc['title'] = $title; 
    $doc['author'] = $author;
    $ret['doc'] = $doc;
    echo json_encode($ret); 
    wp_die();
}

function load_file_list() {
    $uuid = isset($_GET['uuid']) ? $_GET['uuid'] : null;
    echo json_encode(getFileList($uuid) , JSON_NUMERIC_CHECK);
    wp_die();
}

function getFileList(string $uuid = null) : array {
    if($uuid == null) {
        return ["num" => 0 , "files" => "uuid not found"];
    }
    $folder = STORING_DIRECTORY . '/uploads/wooreader/' . $uuid;  
    if(!is_dir($folder)) {
        return ['fileFolderList' => null ];
    }
    $fileList = rFolderContents($folder);
    $docData = get_wooreader_document($uuid);
    $docData[0]->mainfile = isset($docData[0]->mainfile) ? implode('___',explode(DIRECTORY_SEPARATOR, $docData[0]->mainfile)) : null;
    $docData[0]->coverimage = isset($docData[0]->coverimage) ? implode('___',explode(DIRECTORY_SEPARATOR, $docData[0]->coverimage)) : null;
    return [ 'fileFolderList' => $fileList , 'docData' => $docData[0] ];
}

function rFolderContents( string $folder , array $array = array()) : array {
    $list = scandir($folder);
    //return $list;
    foreach ($list as $key => $value) {
        if($value != "." && $value != "..") {
            if(is_dir($folder . DIRECTORY_SEPARATOR . $value)) {
                $folderName = $folder . DIRECTORY_SEPARATOR . $value;
                $array[$value] = array();
                $a = $array[$value];
                $array[$value] = rFolderContents($folderName,$a);
            } else {
                $array[] = $value;
            }
        }
    }
    return $array;
}

function update_default_files() {
    global $wpdb;
    $folder = STORING_DIRECTORY . '/uploads/wooreader/' . $_POST['uuid']; 
    $path = explode('___',$_POST['path']);
    $dirList = implode(DIRECTORY_SEPARATOR,$path);
    $fullFilePath = $dirList . DIRECTORY_SEPARATOR . $_POST['file'];
    //echo json_encode([$folder . $fullFilePath]);
    if(!file_exists($folder . DIRECTORY_SEPARATOR . $fullFilePath)) {
        echo json_encode([false, $folder . DIRECTORY_SEPARATOR . $fullFilePath]);
        wp_die();
    }

    $table = $wpdb->prefix . "wooreader_documents";
    $data = [ $_POST['typeOfFile'] => $fullFilePath ];
    $dataFormat = ["%s"];
    $where = ["uuid" => $_POST['uuid']];
    $whereFormat = ["%s"];
    $doUpdate = $wpdb->update($table , $data , $where , $dataFormat , $whereFormat);

    echo json_encode([(bool)$doUpdate]);
    wp_die();
}
?>