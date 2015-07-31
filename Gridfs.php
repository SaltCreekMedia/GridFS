<?php 

class Gridfs {

    public $MongoObject;

    public function __construct() {
        $this->MongoObject = new Object();
        $this->MongoObject->client = new MongoClient("mongodb://69.64.76.11:37017");
        $this->MongoObject->db = $this->MongoObject->client->selectDB('multi');
    }

    public function readFile($collection = null,$id = null){
        $grid = $this->MongoObject->db->getGridFS($collection);
        $file = $grid->get($id);

        return $file->getBytes();
    }

    public function uploadFiles($files = array()){
        $grid = $this->MongoObject->db->getGridFS('uploads');                    // Initialize GridFS

        $file_ids = array();
        foreach($files as $key => $file){
            if($file['name'] == ''){
                continue;
            }

            $name = $file['name'];        // Get Uploaded file name
            $tmp_name = $file['tmp_name'];        // Get Uploaded file name
            $type = $file['type'];        // Try to get file extension
            $id = $grid->storeFile($tmp_name,array("filename" => $name,"contentType" => $type, "aliases" => null, "metadata" => null));    // Store uploaded file to GridFS
            unlink($tmp_name);

            /* Add additional metadata related to the file if required */
            //$files = $this->MongoObject->db->fs->files;
            //$files->update(array("_id" => $id), array('$set' => array("filename" => $name,"contentType" => $type, "aliases" => null, "metadata" => null)));

            $file_ids[$key]['id'] = $id;
            $file_ids[$key]['name'] = $name;
            $file_ids[$key]['data_type'] = $file['data_type'];
        }

        return $file_ids;
    }

    public function downloadFile($id=null,$displayName){
        $grid = $this->MongoObject->db->getGridFS('uploads');                    // Initialize GridFS

        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);

        if ( (substr($this->file['name'],-3) == 'zip') || (substr($this->file['name'],-3) == 'pdf') ) {
           /* Any file types you want to be downloaded can be listed in this */
           header('Content-Type: application/octet-stream');
           header('Content-Disposition: attachment; filename='.$file->file['name']); 
           header('Content-Transfer-Encoding: binary');
           $cursor = $this->MongoObject->db->fs->chunks->find(array("files_id" => $id))->sort(array("n" => 1));
           foreach($cursor as $chunk) {
              echo $chunk['data']->bin;
           }
        }
        else {
           header('Content-Type: '.$file->file["contentType"]);
           echo $file->getBytes(); 
        }   

        exit(0);
    }
}
class GridfsCsv extends Gridfs {

    protected $file;
    protected $headers;

    public function __construct($fileId = null){
        parent::__construct();
        if(!is_null($fileId)){
            $fileId = new MongoId($fileId);
            $this->file = $this->readFile('csv',$fileId);
        }
    }
    /**
     * @return array()
     */
    public function getHeaders($fileId) {
        if(is_null($this->headers)){
            $Data = str_getcsv($this->file,"\n","\"");
            $this->headers = explode(',',$Data[0]);
        }
        return $this->headers;
    }
    /**
     * @return SplFixedArray
     */
    public function parse($fileId = null) {
        if(!is_null($fileId)){
            $this->__construct($fileId);
        }
        $Data = str_getcsv($this->file,"\n","\"");
        foreach($Data as &$Row) $Row = str_getcsv($Row, ","); //parse the items in rows 

        $spl = SplFixedArray::fromArray($Data);
        return $spl;
    }
    public function uploadSingleFile($file = null){
        $grid = $this->MongoObject->db->getGridFS('csv');
        $name = $file['name'];        // Get Uploaded file name
        $tmp_name = $file['tmp_name'];        // Get Uploaded file name
        $type = $file['type'];        // Try to get file extension
        $id = $grid->storeFile($tmp_name,array("filename" => $name,"contentType" => $type, "aliases" => null, "metadata" => null));    // Store uploaded file to GridFS
        unlink($tmp_name);
        return $id->__toString();
    }

    public function create($csvArray = array(),$createFileName = null){
        $fileString = '';

        while($csvArray->offsetExists($csvArray->key())){
            $fileString .= implode(',',$csvArray->current())."\n";
            $csvArray->next();
        }
        $grid = $this->MongoObject->db->getGridFS('csv');
        $name = $createFileName;        // Get Uploaded file name
        $type = 'text/csv';        // Try to get file extension
        $id = $grid->storeBytes($fileString,array("filename"=>$name,"contentType" => $type, "aliases" => null, "metadata" => null));    // Store uploaded file to GridFS
        return $id->__toString();
    }

    public function downloadCSV($id = null){
        $grid = $this->MongoObject->db->getGridFS('csv');                    // Initialize GridFS

        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);

        header('Content-Type: '.$file->file["contentType"]);
        header('Content-Disposition: attachment; filename='.$file->file['filename']); 
        echo $file->getBytes(); 

        exit(0);
    }

}
?>