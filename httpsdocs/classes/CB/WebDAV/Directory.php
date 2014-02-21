<?php
namespace CB\WebDAV;

class Directory extends \Sabre\DAV\FS\Node implements \Sabre\DAV\ICollection
{
    private $myPath;

    public $id;
    protected $parent;

    public $content;
    private $objectData;
    private $only;

    public function __construct($myPath, $parent = null, $only = null, $root = null, $objectData = null)
    {
        $this->only = $only;

        if ($myPath == $root) {
            $this->id = 1;
        } else {
            foreach ($parent->content as $node) {
                if ($node['path'] == $myPath) {
                    $this->id = $node['id'];
                    break;
                }
            }
        }

        $this->parent = $parent;
        $this->myPath = $myPath;
        $this->content = Utils::getNodeContent($this->id, $this->myPath);
        $this->objectData = is_null($objectData)
            ? Utils::getNodeById($this->id)
            : $objectData;
    }

    public function getChildren()
    {
        $children = array();
        // Loop through the directory, and create objects for each node

        foreach ($this->content as $node) {
            if ($this->only == null) {
                $children[] = $this->getChild($node['name']);
            } else {
                if ($this->only[0] == $node['id']) {
                    $children[] = $this->getChild($node['name']);
                }
            }
        }

        return $children;
    }

    public function getChild($name)
    {
        $path = $this->myPath . WEBDAV_PATH_DELIMITER . $name;
        foreach ($this->content as $item) {
            if ($item['name'] == $name) {
                if ($item['template_id'] != \CB\CONFIG\DEFAULT_FILE_TEMPLATE) {
                    return new Directory($path, $this, null, null, $item);
                } else {
                    return new File($path, $item['id'], $this, $item);
                }
            }
        }

        throw new \Sabre\DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
    }

    public function childExists($name)
    {
        foreach ($this->content as $item) {
            if ($item['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    public function getName()
    {
        return basename($this->myPath);
    }

    public function setName($name)
    {
        Utils::renameObject($this->id, $name);
    }

    //method for creation date is also required ???
    public function getLastModified()
    {
        return ( empty($this->objectData['udate'])
            ? $this->objectData['cdate']
            : $this->objectData['udate']
        );
    }

    public function createDirectory($name)
    {
        return Utils::createDirectory($this->id, $name);
    }

    public function createFile($name, $data = null)
    {
        Utils::createCaseboxFile($this->id, $name, $data);
    }

    public function delete()
    {
        Utils::deleteObject($this->id);
    }
}
