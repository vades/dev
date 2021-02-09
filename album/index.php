<?php
class Album
{
    private $dir = 'albums';
    private $albums = [];
    private $mainImageDir = 'large';
    private $addonImageDirs = ['thumbnail','small', 'medium'];
    private $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $coverImage = 'cover.jpg';
    private $domain = '';

    public function __construct(?string $dir = null)
    {
        if (!empty($dir)) {
            $this->setDir($dir);
        }
        $this->setDomain($this->buildDomainLink());
        $this->buildAlbum();
    }

    public function buildAlbum()
    {
        $output = [];
        $iterator = new \DirectoryIterator($this->getDir());

        foreach ($iterator as $fileinfo) {
            $file_name = $fileinfo->getFilename();
            if ($fileinfo->isDot() || strpos($file_name, '.')) {
                continue;

            }
            $album_path = $this->getDir() .  '/' . $file_name;
            if (!is_dir($album_path)) {
                continue;
            }
            $album = [];
            $album['rootEndpoint'] = $this->getDomain();
            $album['name'] = $file_name;
            $album['path'] =  '/' . $album_path;
            $album['events'] = $this->buildAlbumEvent($album_path);

            array_push($output, $album);
        }

        $this->setAlbums($output);
        return;
    }

    private function buildAlbumEvent($dir)
    {
        $output = [];
        $iterator = new \DirectoryIterator($dir);

        foreach ($iterator as $fileinfo) {
            $file_name = $fileinfo->getFilename();
            if ($fileinfo->isDot() || strpos($file_name, '.')) {
                continue;

            }
            $album_path = $dir .  '/' . $file_name;
            if (!is_dir($album_path)) {
                continue;
            }

            $event = [];
            $event['name'] = $file_name;
            $event['path'] =  '/' . $album_path;
            $event['cover'] =  $this->getImage($album_path . '/' . $this->coverImage);
            $event['images'] =  $this->buildEventImages($album_path);
            array_push($output, $event);
        }

        return $output;
    }

    private function buildEventImages($dir)
    {
        $output = [];
        $main_image_dir = $dir.'/'.$this->mainImageDir;
        $iterator = new \DirectoryIterator($main_image_dir);

        foreach ($iterator as $fileinfo) {
            $file_name = $fileinfo->getFilename();

            if ($fileinfo->isDot() || !$this->imageHasAllowedExtension($file_name)) {
                continue;
            }
            
            $image_path = $main_image_dir .  '/' . $file_name;
            $main_image = $this->getImage($image_path);
            if (empty($main_image) ) {
                continue;
            }
           
            $images = [];
            $addon_images = $this->getAddonImages($dir,$file_name);
            if (!empty($addon_images) ) {
                $images =  $addon_images;
            }
            $images[$this->mainImageDir] =  $main_image;
            array_push($output, $images);

           
        }

        return $output;
    }

   

    private function getAddonImages($dir, $file_name)
    {
        $output = [];


        foreach ($this->addonImageDirs as $image_dir) {
            $image_path = $dir .  '/' . $image_dir. '/'.$file_name;

            $addon_image = $this->getImage($image_path);
            if (empty($addon_image) ) {
                continue;
            }
            $output[$image_dir] =  $addon_image;
        }

        return $output;
    }

    private function getImage($image_path)
    {
        $image = [];
        if (is_file($image_path)) {
            $image['src'] = $this->getDomain() . '/' . $image_path;
            $image['meta'] = $this->getImageMeta($image_path);
        }
        return $image;
    }

    private function getImages($dir)
    {
        $output = [];
        $iterator = new \DirectoryIterator($dir);


        foreach ($iterator as $fileinfo) {
            $file_name = $fileinfo->getFilename();

            if ($fileinfo->isDot()) {
                continue;
                //echo $fileinfo->getFilename() . "\n";

            }
            $image_path = $dir .  '/' . $file_name;
            if (!is_file($image_path) || !$this->imageHasAllowedExtension($file_name)) {
                continue;
            }
            $image = [];
            $image_url = $this->getDomain() . '/' . $image_path;
            $image['src'] =  $image_url;
            $image['meta'] = $this->getImageMeta($image_path);

            array_push($output, $image);
        }

        return $output;
    }

    private function getImageMeta($image_path)
    {
        $out = [];
        $meta = getimagesize($image_path);
        if ($meta) {
            $out['width'] = $meta[0];
            $out['height'] = $meta[1];
            $out['mime'] = $meta['mime'];
            $out['orientation'] = $this->getImageOrientaton($out['width'], $out['height']);
        }
        return $out;
    }

    private function getImageOrientaton($width, $height){
        if($height > $width){
            return 'portrait';
        }elseif($width === $height){
            return 'square';
        }else{
            return 'landscape';
        }
    }

    private function buildDomainLink()
    {
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
        $domain_link = rtrim($domain,"/");
        return $domain_link;
    }

    /**
     * Check if file has allowed extension
     * 
     * @return bool
     */
    private function imageHasAllowedExtension(string $filename): bool
    {
        $file_extension = pathinfo(strtolower($filename), PATHINFO_EXTENSION);
        return in_array($file_extension, $this->imageExtensions);
    }

    public function jsonResponse(?array $albums = [], int $status = 200, string $message = 'OK')
    {
        $out = [
            'status' => $status,
            'message' => $message,
            'albums' => $albums
        ];
        // clear the old headers
        header_remove();
        // set the actual code
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS,PATCH');
        header('Access-Control-Allow-Origin: *');
        header('Status: ' . $status);

        echo json_encode($out, JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    /**
     * Get the value of dir
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Set the value of dir
     *
     * @return  self
     */
    public function setDir($dir)
    {
        $this->dir = $dir;

        return $this;
    }

    /**
     * Get the value of albums
     */
    public function getAlbums()
    {
        return $this->albums;
    }

    /**
     * Set the value of albums
     *
     * @return  self
     */
    public function setAlbums($albums)
    {
        $this->albums = $albums;

        return $this;
    }

    /**
     * Get the value of domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the value of domain
     *
     * @return  self
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }
}

$album = new Album();
$album->jsonResponse($album->getAlbums());
