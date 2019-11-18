<?php

class Util_Workflow_Graph_Framework
{

    private $width  = null;
    private $height = null;

    private $color = null;
    private $image = null;

    private $placeArray      = array();
    private $transitionArray = array();

    public function __construct($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;


        $this->image = @imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($this->image, true);
    }

    public function setTitel($message, $a, $b, $c)
    {
        $text_color = imagecolorallocate($this->image, $a, $b, $c);
        imagestring($this->image, 1, 5, 5, $message, $text_color);
    }

    public function setColor($a, $b, $c, $d)
    {
        // set white color
        $trans_colour = imagecolorallocatealpha($this->image, $a, $b, $c, $d);
        imagefill($this->image, 0, 0, $trans_colour);
    }


    public function addPlace(Util_Workflow_Graph_Place $place)
    {
        $countLeft = count($this->placeArray);

        $positionLeft = 250 * $countLeft;
        // $positionLeft += x; // Transition und arcs
        $positionLeft += 10;

        $countTop = $place->getEbene();

        $positionTop = 300 * $countTop;
        $positionTop += 30;

        $place->getImage();

        imagecopymerge(
            $this->image,
            $place->getImage(),
            $positionLeft,
            $positionTop,
            0,
            0,
            imagesx($place->getImage()),
            imagesy($place->getImage()),
            50
        );


        array_push($this->placeArray, $place);
    }

    public function addTransition(Util_Workflow_Graph_Transition $transition)
    {
        array_push($this->transitionArray, $transition);
    }


    public function addArc()
    {
        // TODO:
    }

    public function render()
    {
        // TODO: process all connections now??

        imagepng($this->image);
        imagedestroy($this->image);
    }
}