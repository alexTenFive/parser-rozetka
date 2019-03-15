<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //The name of the folder.
    $folders = [XLSX_PATH, XML_PATH];
    
    //Get a list of all of the file names in the folder.

    foreach ($folders as $folder) {
        $files = glob($folder . '/*');
    
        //Loop through the file list.
        foreach($files as $file){
            //Make sure that this is a file and not a directory.
            if(is_file($file)){
                //Use the unlink function to delete the file.
                unlink($file);
            }
        }
    }

    header('Location: /convert');
}

