@push('hls-styles')
    <link rel="stylesheet" href="https://releases.transloadit.com/uppy/v3.18.0/uppy.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <style>
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

        .uppy-Dashboard-inner,
        .uppy-StatusBar.is-waiting .uppy-StatusBar-actions,
        .uppy-DashboardContent-bar {
            background: white;
        }

        .uppy-Dashboard-Item-previewInnerWrap,
        .uppy-Dashboard--singleFile .uppy-Dashboard-Item-previewInnerWrap,
        .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload,
        .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload:hover {
            background: rgb(52 152 220) !important;
        }
    </style>
@endpush

<div id="video-options-card"></div>
<div class="col-md-12 text-center" id="video_loader">
    <span class="loader"></span>
</div>


@push('hls-scripts')
    <script src="https://releases.transloadit.com/uppy/v3.18.0/uppy.min.js"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        let uppy = null;
        @if ($video?->id)
            let videoId = $video ? - > id;
        @else
            let videoId = null;
        @endif


        function setupVideoUpload() {
            uppy = new Uppy.Uppy({
                restrictions: {
                    maxNumberOfFiles: 1, // ✅ Allow only one file
                    maxFileSize: 1000000000, // 50MB
                    allowedFileTypes: [
                        'video/*' // ✅ Accept ALL video formats
                    ]
                },
                autoProceed: false, // Automatically start uploading after the file is selected
                parallel: true, // Enable parallel uploads of chunks
                chunkSize: 10 * 1024 * 1024 // 10MB per chunk (can be adjusted)
            });

            // ✅ Remove previous file when a new one is added
            uppy.on('file-added', (file) => {
                if (uppy.getFiles().length > 1) {
                    const previousFile = uppy.getFiles()[0]; // Get the first file
                    uppy.removeFile(previousFile.id); // Remove the previous file
                }
            });

            // ✅ Add Dashboard UI with remote upload options
            uppy.use(Uppy.Dashboard, {
                inline: true,
                target: "#drag-drop-area", // The element where Uppy will be displayed
                showProgressDetails: true,
                proudlyDisplayPoweredByUppy: false,
                plugins: [
                    'GoogleDrive',
                    'Url',
                    // 'Dropbox', 'Instagram', 'OneDrive', 
                    //'Webcam', 'ScreenCapture', 'Url'
                ]
            });

            // Set meta before upload
            uppy.setMeta({
                model_type: "{{ str_replace('\\','\\\\',get_class($model)) }}",
                model_id: "{{ $model->id }}",
            });

            // Add XHRUpload plugin for chunk upload handling
            uppy.use(Uppy.XHRUpload, {
                endpoint: '{{ route('hls.videos.upload') }}', // Your backend endpoint for receiving chunks
                formData: true,
                fieldName: 'file',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                // You can add custom options for parallelism and retries
                parallelUploads: 5, // Limit to 5 parallel uploads
                // Other useful options:
                allowMultipleUploads: false
                // - withCredentials: true (if you need to send credentials with requests)
            });

            uppy.on('upload-success', (file, response) => {
                videoId = response.body.video_id;
                setVideoOptionCard(response.body);
            });
        }

        function refreshVideoViewContent() {
            if (videoId) {
                var url = '{{ route('hls.videos.options', ':id') }}';
                url = url.replace(':id', videoId);
            } else {
                var url = '{{ route('hls.videos.options') }}';
            }

            $.ajax({
                url: url,
                method: 'GET',

                beforeSend: function() {
                    $("#video-options-card").html('');
                    $('#video_loader').show();
                },
                success: function(response) {
                    setVideoOptionCard(response);
                },
                error: function(xhr, status, error) {
                    toastr["error"]("Error loading video options");
                    $('#video_loader').hide();
                }
            });
        }

        function setVideoOptionCard(response) {
            $("#video-options-card").html(response.html);
            $('#video_loader').hide();
            if (response.build_uploader)
                setupVideoUpload();

            if (response.is_ready) {
                videoPlayerIoRun(response.video_source)
            }
        }

        function deleteVideo(url) {
            var _token = $('input[name=_token]').val();

            bootbox.confirm({
                message: 'undefined',
                buttons: {
                    confirm: {
                        label: '{{ __('Yes') }}',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: '{{ __('No') }}',
                        className: 'btn-danger'
                    }
                },

                callback: function(result) {
                    if (result) {

                        $("#video-options-card").html('');
                        $('#video_loader').show();
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            data: {
                                _token: _token
                            },
                            success: function(response) {
                                setVideoOptionCard(response);
                            },
                            error: function(msg) {
                                toastr["error"](msg[1]);
                            }
                        });
                    }
                }
            });
        }

        function videoPlayerIoRun(source) {
            let video = document.getElementById('player');

            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(source);
                hls.attachMedia(video);
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = source;
            }

            const player = new Plyr(video);
        }
    </script>
    <script>
        $(document).ready(function() {
            refreshVideoViewContent();
        })
    </script>
@endpush
