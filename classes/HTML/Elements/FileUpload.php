<?php
namespace \VGWS\HTML\Elements;
class FileUpload extends Input
{
    public $validTypes = array();
    public $uploadData = null;

    public function __construct($name, array $validTypes, array $other = array())
    {

        $other['type'] = 'file';
        $this->validTypes = $validTypes;
        parent::__construct('file', $name, '', $other);
    }

    /**
     * MUST BE CALLED OR SHIT WILL BREAK.
     */
    public function Validate($devnull = array())
    {
        $name = $this->attributes['name'];
        if ($this->required) {
            if (!isset($_FILES[$name]) || $_FILES[$name]['size'] == 0) {
                Page::Message('error', 'This field is required.', $this->attributes['name']);
                return false;
            }
        }

        // Don't process further if unset.
        if (!isset($_FILES[$name]) || $_FILES[$name]['size'] == 0)
            return true;

        $data = $_FILES[$name];
        $data['type'] = getMime($data['tmp_name']);

        // Max file size checks
        if ($this->maxLength > 0) {
            if ($data['size'] > $this->maxLength) {
                $fmtSize = formatBytes($data['size']);
                Page::Message('error', "You cannot upload files exceeding {$fmtSize}.", $this->attributes['name']);
                $this->GC($data['tmp_name']);
                return false;
            }
        }

        // Check MIME type (keeps people from uploading PHP files)
        if ($this->validTypes != array()) {
            if (!array_key_exists($data['type'], $this->validTypes)) {
                $validFileExts = array();
                foreach ($this->validTypes as $type => $exts) {
                    foreach ($exts as $ext) {
                        if (startsWith($ext, '.'))
                            $validFileExts[] = $ext;
                        else
                            $validFileExts[] = '.' . $ext;
                    }
                }
                Page::Message('error', "You uploaded an unacceptable file type: The file you uploaded isn't a " . join_english($validFileExts, '[EMPTY ARRAY!]', ' or a ') . '.', $name);
                $this->GC($data['tmp_name']);
                return false;
            }
            // Standardize extension
            $data['name'] = replace_extension($data['name'], $this->validTypes[$data['type']][0]);
        }

        // Check image size
        if (array_key_exists('max_height', $this->attributes) && array_key_exists('max_width', $this->attributes)) {
            $size = getimagesize($data['tmp_name']);
            if (!$size) {
                Page::Message('error', 'Failed to read the size of uploaded image.');
                $this->GC($data['tmp_name']);
                return false;
            }
            $fails = 0;
            if ($size[0] > intval($this->attributes['max_width'])) {
                Page::Message('error', "Uploaded image is {$size[0]}px wide, must be under {$this->attributes['max_width']}px wide.", $this->attributes['name']);
                $fails++;
            }
            if ($size[1] > intval($this->attributes['max_height'])) {
                Page::Message('error', "Uploaded image is {$size[1]}px wide, must be under {$this->attributes['max_height']}px high.", $this->attributes['name']);
                $fails++;
            }
            if ($fails) {
                $this->GC($data['tmp_name']);
                return false;
            }
        }

        $this->uploadData = $data;

        return true;
    }

    public function Cleanup()
    {
        if ($this->uploadData == null)
            return;
        $this->GC($this->uploadData['tmp_name']);
    }

    private function AssertWasValidated()
    {
        squad_assert('FileUpload not validated prior to attempting data access.', $this->uploadData != null);
    }

    private function GC($file)
    {
        if (file_exists($file))
            unlink($file);
    }

    public function GetMD5()
    {
        if ($this->uploadData == null)
            return null;
        return md5_file($this->uploadData['tmp_name']);
    }

    public function GetExt()
    {
        if ($this->uploadData == null)
            return null;
        $mime = getMime($this->uploadData['tmp_name']);
        if (array_key_exists($mime, $this->validTypes))
            return $this->validTypes[$mime][0];
        return 'bin';
    }

    public function MoveFileTo($dest)
    {
        if ($this->uploadData == null)
            return;
        $dir = dirname($dest);
        if (!file_exists($dir))
            mkdir($dir, 0755, true);
        return move_uploaded_file($this->uploadData["tmp_name"], $dest);
    }

    public function GetTemporaryFilename()
    {
        return $this->uploadData["tmp_name"];
    }

    public function GetOriginalFilename()
    {
        if ($this->uploadData == null)
            return null;
        return $this->uploadData['name'];
    }

}
