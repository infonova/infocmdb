<?php

class Util_Workflow_Graph_Place
{

    private $position = 0;
    private $ebene    = 0;

    private $titel = null;

    private $image = null;

    public function __construct($position = 0, $ebene = 0)
    {
        $this->position = $position;
        $this->ebene    = $ebene;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setEbene($ebene)
    {
        $this->ebene = $ebene;
    }

    public function getEbene()
    {
        return $this->ebene;
    }

    public function setTitel($message)
    {
        $this->titel = $message;
    }

    public function getImage()
    {
        if (!$this->image) {
            $this->image = imagecreatetruecolor(75, 75);
            $color       = imagecolorallocate($this->image, 205, 197, 191);
            imagefill($this->image, 0, 0, $color);

            if ($this->titel) {
                $text_color = imagecolorallocate($this->image, 0, 0, 0);
                imagestring($this->image, 2, 9, 28, $this->titel, $text_color);
            }
        }

        return $this->image;
    }
}