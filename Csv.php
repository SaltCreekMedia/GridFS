<?php 
App::uses('Component', 'Controller');

class CsvComponent extends Component {

    public $components = array('Session','Gridfs');
    public $helpers = array('Session');
    public $MongoObject;

    public function initialize(Controller $controller ) {
        $this->controller = $controller;
    }

    public function startup(Controller $controller ) {
    }

    /**
     * @return array()
     */
    public function getHeaders($fileId) {
        $fileId = new MongoId($fileId);
        $file = $this->Gridfs->readFile('csv',$fileId);

        $Data = str_getcsv($file,"\n","\"");

        return explode(',',$Data[0]);
    }
    /**
     * @return SplFixedArray
     */
    public function parse($fileId) {
        $fileId = new MongoId($fileId);
        $file = $this->Gridfs->readFile('csv',$fileId);

        $Data = str_getcsv($file,"\n","\"");

        foreach($Data as &$Row) $Row = str_getcsv($Row, ","); //parse the items in rows 

        $spl = SplFixedArray::fromArray($Data);
        return $spl;
    }

}
