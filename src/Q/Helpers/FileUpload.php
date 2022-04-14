<?php
/**
 * Simple PHP File upload wrapper
 * @author quantm.tb@gmail.com
 * @date: 15/04/2016 13:21
 */
namespace Q\Helpers;

class FileUpload
{
    private static $whiteListExtensions = [
        'jpg', 'png', 'jpeg', 'gif', 'bmp', 'doc', 'docx', 'xls', 'xlsx'
    ];

    private $file;
    private $extension;
    private $mimeType;
    private $size;
    private $error;
    private $originalName;
    private $tmpName;

    /**
     * FileUpload constructor.
     * @param array $file
     */
    public function __construct(array $file)
    {
        $this->file = $file;

        if (function_exists('pathinfo')) {
            $this->extension = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
        } else {
            if (strpos($this->file['name'], '.') !== false) {
                $exploded = explode('.', $this->file['name']);
                $this->extension = strtolower(array_pop($exploded));
            }

        }


        $this->mimeType = $this->file['type'];
        $this->size = $this->file['size'];
        $this->error = $this->file['error'] != 0;
        $this->originalName = $this->file['name'];
        $this->tmpName = $this->file['tmp_name'];
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error == UPLOAD_ERR_OK) {
            return '';
        }

        switch ($this->error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }

    /**
     * @return mixed
     */
    public function originalName()
    {
        return $this->originalName;
    }

    /**
     * @return mixed
     */
    public function getTmpName()
    {
        return $this->tmpName;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        return @is_array(getimagesize($this->tmpName));
    }

    /**
     * @param $path
     * @param bool $autoCreateSubDir
     * @return string
     * @throws \Exception
     */
    public function moveTo($path, $autoCreateSubDir = false, $resizeImage = false)
    {

        if (!is_dir($path)) {
            throw new \Exception("$path is not a directory");
        }

        if (!is_writable($path)) {
            throw new \Exception("$path is not writable");
        }

        $newFilename = sha1($this->originalName) . '.' . $this->extension;
        if ($autoCreateSubDir) {
            $month = date('m');
            $year = date('Y');

            $dir = $path . '/' . $year . '/' . $month . '/';

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $newFile = '/' . $year . '/' . $month . '/' . uniqid() . '_' . $newFilename;
        } else {
            $newFile = '/' . uniqid() . '_' . $newFilename;
        }

        $uploadFile = $path . $newFile;

        $result = move_uploaded_file($this->tmpName, $uploadFile);

        if (!$result) {
            throw new \Exception('Could not move upload file');
        }

        return $newFile;
    }


    /**
     * @return FileUpload[]
     */
    public static function fromGlobal()
    {
        $result = [];

        if (!empty ($_FILES)) {
            foreach ($_FILES as $name => $file){
                $isMultiple = is_array($file['name']);
                if ($isMultiple) {
                    $fileCount = count($file['name']);
                    $files = [];
                    for ($i = 0; $i < $fileCount; $i++) {
                        $files[] = new static([
                            'name' => $file['name'][$i],
                            'type' => $file['type'][$i],
                            'tmp_name' => $file['tmp_name'][$i],
                            'error' => $file['error'][$i],
                            'size' => $file['size'][$i],
                        ]);

                    }
                    $result[$name] = $files;
                } else {
                    $result[$name] = new static($file);

                }

            }

        }

        return $result;
    }
}