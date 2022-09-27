<?php
require 'header.php'; 
$error   = '';
 
// default value bila tidak masuk dalam mode edit
$title   = '';
$posting = '';
$button  = 'Publish';
$cats    = array();
$update  = '<input type="hidden" name="post-add" value="true"/>';
 
// Aksi Simpan ke database
if (isset($_POST['post-add'])) {
 
    /**
     * $db adalah koneksi database. lihat admin-loader.php
     */
    $posting = $db->escape_string($_POST['post']);
    $author  = $db->escape_string($_SESSION['user_ID']);
    $title   = $db->escape_string($_POST['title']);
    $category= $_POST['category'];
    $excerpt = '';
 
    // check apakah menggunakan readmore atau tidak
    $pagebreak = $db->escape_string('<div style="page-break-after: always">');
    if (strpos($posting, $pagebreak) !== false ) {
        // ambil kalimat sebelum page-break
        $post    = explode($pagebreak, $posting); 
        $excerpt = $post[0];
    } else {
        // ambil 50 kata awal paragraf
        $post    = strip_tags($posting);
        $excerpt = implode(' ', array_slice(explode(' ', $post), 0, 50));
    } 
 
    // SQL Command To Insert
    $sql = "INSERT INTO posting 
                    (iduser, title, content, excerpt) 
            VALUES 
                    ('$author', '$title', '$posting', '$excerpt')";
    
    // Eksekusi Simpan
    $insert = $db->query($sql);
 
    if ($insert) {
        // Get Last Id posting after insert
        $idposting = $db->insert_id;
 
        if (is_array($category) && count($category) > 0) {
            $sqlcat = "INSERT INTO cat_post (idcat, id_post) VALUES ";
                
            // setup multiple insert di table cat_post
            $cat_count = count($category);
            for ($i=0; $i < $cat_count; $i++) { 
                $sqlcat .= "('{$category[$i]}', '{$idposting}'),";
            }
            
            $sqlcat = rtrim($sqlcat, ',');
 
            // execute insert onto database
            if ($db->query($sqlcat)) {
                header('location:'.$domain.'admin/posts.php?insert=true&edit='.$idposting);
                exit();
            } else {
                $error = 'Gagal insert category';
            }
        }
        header('location:'.$domain.'admin/posts.php?insert=true&edit='.$idposting);
        exit();
    } else {
        $error = 'Gagal Insert Posting';
    }
}
 
/**
 * Eksekusi Update
 * Eksekusi ini akan berjalan apabila ada ada $_POST dari mode Edit
 */
if (isset($_POST['update-post'])) {
    /**
     * $db adalah koneksi database. lihat admin-loader.php
     */
    $id_post = $db->escape_string($_POST['update-post']); // id post yg hendak diupdate
    $posting = $db->escape_string($_POST['post']);
    $author  = $db->escape_string($_SESSION['user_ID']);
    $title   = $db->escape_string($_POST['title']);
    $category= isset($_POST['category']) ? $_POST['category'] : '';
    $excerpt = '';
 
    // check apakah menggunakan readmore atau tidak
    $pagebreak = $db->escape_string('<div style="page-break-after: always">');
    if (strpos($posting, $pagebreak) !== false ) {
        // ambil kalimat sebelum page-break
        $post    = explode($pagebreak, $posting); 
        $excerpt = $post[0];
    } else {
        // ambil 50 kata awal paragraf
        $post    = strip_tags($posting);
        $excerpt = implode(' ', array_slice(explode(' ', $post), 0, 50));
    }
 
    $sql = "UPDATE 
                posting 
            SET 
                iduser='$author',
                content='$posting',
                title='$title',
                excerpt='$excerpt'
            WHERE 
                id_post='$id_post' ";
    $update = $db->query($sql);
    if ($update) {
        // jika ada kategori
        if (is_array($category) && count($category) > 0) {
            $select = "DELETE FROM cat_post WHERE id_post='$id_post'";
            if ($db->query($select)) {
                $cat_count = count($category);
                
                $sqlcat = "INSERT INTO cat_post (idcat, id_post) VALUES ";
                
                for ($i=0; $i < $cat_count; $i++) { 
                    $sqlcat .= "('{$category[$i]}', '{$id_post}'),";
                }
                
                $sqlcat = rtrim($sqlcat, ',');
 
                // execute insert onto database
                if ($db->query($sqlcat)) {
                    header('location:'.$domain.'admin/posts.php?update=true&edit='.$id_post);
                    exit();
                } else {
                    $error = 'Gagal UPDATE category';
                }
            }
        }   
    }
 
}
 
 
/**
 * Eksekusi Edit
 * Eksekusi ini akan berjalan saat masuk mode edit
 */
if (isset($_GET['edit'])) {
    $idpost = $_GET['edit'];
 
    // mengambil post dari database
    $sql = "SELECT title, content FROM posting WHERE id_post = '$idpost' LIMIT 1";
    $result = $db->query($sql);
 
    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        $title   = $post['title'];
        $posting = $post['content'];
        $button  = 'Update';
        $update  = '<input type="hidden" name="update-post" value="'.$idpost.'"/>';
    
    } else {
        header('location:'.$domain.'admin/posts.php');
        exit();
    }
 
    /**
     * Selection Categories untuk menyeleksi kategori yang sudah dipilih
     */
    $sql = "SELECT 
                Cp.idcat 
            FROM 
                cat_post Cp, 
                categories Cs 
            WHERE 
                Cp.idcat=Cs.idcat 
            AND 
                Cp.id_post='$idpost'";
 
    $categories = $db->query($sql);
    if ($categories->num_rows > 0) {
        $cats = array();
        while ($cat = $categories->fetch_assoc()) {
            $cats[] = $cat['idcat'];
        }
    }
}
 
/**
 * Eksekusi DELETE
 * apabila ada parameter (GET) ../posts.php?delete=ID di address bar
 */
if (isset($_GET['delete']) &&
    !empty($_GET['delete']) &&
    is_numeric($_GET['delete'])
    ) {
    $id_post = $_GET['delete'];
 
    $posting = "DELETE FROM posting WHERE id_post='$id_post'";
    $select  = "DELETE FROM cat_post WHERE id_post='$id_post'";
    $db->query($posting);
    $db->query($select);
    header('location:'.$domain.'admin/all-post.php?delete=sukses');
    exit();
}
?>
<div class="row header">
    <div class="col-md-2 title-site "><h2>ONPanel</h2></div>
    <div class="col-md-8 title-page"><h2>Halaman Posting</h2></div>
    <div class="col-md-2 text-right author-shortcut">hi, <?=$_SESSION['user_login'];?></div>
</div>
<div class="row">
    <?php require 'sidebar.php';?>
    <div class="col-md-7">
        <form method="post">
            <?=$update;?>
 
            <?php 
            /**
             * Report for Insert, success or error
             */
            echo $error;
            echo (isset($_GET['insert']) && $_GET['insert'] == 'true') ? 'Sukses':'';
            ?>
            <div class="form-group">
                <input type="text" class="form-control" name="title" value="<?=$title;?>" placeholder="Title..."/>
            </div>
            <textarea name="post" id="post-1" rows="10" cols="80"><?=$posting;?></textarea>
            <script>
                // Replace the <textarea id="editor1"> with a CKEditor
                // instance, using default configuration.
                CKEDITOR.replace('post-1', {
                    toolbar: [
                        { name: 'basicstyles', items: [ 'Format', 'FontSize', 'Source', 
                                                        'Bold', 
                                                        'Italic', 
                                                        'Underline', 
                                                        '-', 
                                                        'JustifyLeft', 
                                                        'JustifyCenter', 
                                                        'JustifyRight', 
                                                        'JustifyBlock', 
                                                        '-', 
                                                        'Undo', 
                                                        'Redo', 
                                                        'PageBreak', 'Link', 'Unlink', 'Image' ] },
                    ]
                });
            </script>
            <br>
            <button class="btn btn-primary btn-sm"><?=$button;?></button>
        
    </div>
    
    <div class="col-md-3">
        <h3 class="right-side-title">Categories</h3>
        <?php
            // Tampilankan Category
            $sql = "SELECT * FROM categories ORDER BY idcat DESC";
    
            // $db -> lihat admin-loader.php
            $result = $db->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // selected
                    $checked = (in_array($row['idcat'], $cats)) ? 'checked':'';
                    echo '<input type="checkbox" name="category[]" value="'.$row['idcat'].'" '.$checked.'/>  '.$row['category'];
                }
            } else {
                echo '<i>Tambah category di halaman Categories</i>';
            }
        ?>
    </div>
    </form>
</div>
<?php require 'footer.php';?>
