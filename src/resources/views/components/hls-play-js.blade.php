
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <style>
        .loader2 {
            width: 175px;
            height: 80px;
            display: block;
            margin: auto;
            background-image: radial-gradient(circle 25px at 25px 25px, #FFF 100%, transparent 0), radial-gradient(circle 50px at 50px 50px, #FFF 100%, transparent 0), radial-gradient(circle 25px at 25px 25px, #FFF 100%, transparent 0), linear-gradient(#FFF 50px, transparent 0);
            background-size: 50px 50px, 100px 76px, 50px 50px, 120px 40px;
            background-position: 0px 30px, 37px 0px, 122px 30px, 25px 40px;
            background-repeat: no-repeat;
            position: relative;
            box-sizing: border-box;
        }

        .loader2::before {
            content: '';
            left: 60px;
            bottom: 18px;
            position: absolute;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #FF3D00;
            background-image: radial-gradient(circle 8px at 18px 18px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 18px 0px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 0px 18px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 36px 18px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 18px 36px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 30px 5px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 30px 5px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 30px 30px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 5px 30px, #FFF 100%, transparent 0), radial-gradient(circle 4px at 5px 5px, #FFF 100%, transparent 0);
            background-repeat: no-repeat;
            box-sizing: border-box;
            animation: rotationBack 3s linear infinite;
        }

        .loader2::after {
            content: '';
            left: 94px;
            bottom: 15px;
            position: absolute;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #FF3D00;
            background-image: radial-gradient(circle 5px at 12px 12px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 12px 0px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 0px 12px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 24px 12px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 12px 24px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 20px 3px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 20px 3px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 20px 20px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 3px 20px, #FFF 100%, transparent 0), radial-gradient(circle 2.5px at 3px 3px, #FFF 100%, transparent 0);
            background-repeat: no-repeat;
            box-sizing: border-box;
            animation: rotationBack 4s linear infinite reverse;
        }

        @keyframes rotationBack {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(-360deg);
            }
        }
    </style>
    <style>
        .loader {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #fff;
            box-shadow: 32px 0 #fff, -32px 0 #fff;
            position: relative;
            animation: flash 0.3s ease-in infinite alternate;
        }

        .loader::before,
        .loader::after {
            content: '';
            position: absolute;
            left: -64px;
            top: 0;
            background: #FFF;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            transform-origin: 35px -35px;
            transform: rotate(45deg);
            animation: hitL 0.3s ease-in infinite alternate;
        }

        .loader::after {
            left: 64px;
            transform: rotate(-45deg);
            transform-origin: -35px -35px;
            animation: hitR 0.3s ease-out infinite alternate;
        }

        @keyframes flash {

            0%,
            100% {
                background-color: rgba(255, 255, 255, 0.25);
                box-shadow: 32px 0 rgba(255, 255, 255, 0.25), -32px 0 rgba(255, 255, 255, 0.25);
            }

            25% {
                background-color: rgba(255, 255, 255, 0.25);
                box-shadow: 32px 0 rgba(255, 255, 255, 0.25), -32px 0 rgba(255, 255, 255, 1);
            }

            50% {
                background-color: rgba(255, 255, 255, 1);
                box-shadow: 32px 0 rgba(255, 255, 255, 0.25), -32px 0 rgba(255, 255, 255, 0.25);
            }

            75% {
                background-color: rgba(255, 255, 255, 0.25);
                box-shadow: 32px 0 rgba(255, 255, 255, 1), -32px 0 rgba(255, 255, 255, 0.25);
            }
        }

        @keyframes hitL {
            0% {
                transform: rotate(45deg);
                background-color: rgba(255, 255, 255, 1);
            }

            25%,
            100% {
                transform: rotate(0deg);
                background-color: rgba(255, 255, 255, 0.25);
            }
        }

        @keyframes hitR {

            0%,
            75% {
                transform: rotate(0deg);
                background-color: rgba(255, 255, 255, 0.25);
            }

            100% {
                transform: rotate(-45deg);
                background-color: rgba(255, 255, 255, 1);
            }
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #337ab7;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        function playHls(videoId,thumb) {
            let loader = document.getElementById('video_loader');
            let content = document.getElementById('video-options-card');
            let src = "{{ route(config('hls-videos.access_route_stream'), ['::id']) }}";

            loader.style.display = 'block';
            content.style.display = 'none';
            src = src.replace('::id', videoId);

            let video = `
                <video id="hls-player-${videoId}" playsinline controls poster="${thumb}">
                    <source src="${src}" type="application/x-mpegURL">
                </video>`
            ;
            
            content.innerHTML = video;

            videoPlayerIoRun(videoId,src);

            loader.style.display = 'none';
            content.style.display = 'block';
        }

        function videoPlayerIoRun(videoId,source) {
            let video = document.getElementById(`hls-player-${videoId}`);

            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(source);
                hls.attachMedia(video);
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = source;
            }

            const player = new Plyr(video);
            player.play();
        }
    </script>