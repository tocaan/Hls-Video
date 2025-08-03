<?php
namespace  HlsVideos\Components;

use Illuminate\View\Component;

class HlsPlayerScript extends Component
{
    public function __construct()
    {
    }

    public function render()
    {
        return view('hls-videos::components.hls-play-js');
    }
}
