<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Lumen log viewer</title>
    <link rel="stylesheet" href="{{ config('logviewer.blade.bootstrap.css') }}">
    <link rel="stylesheet" href="{{ config('logviewer.blade.dataTables.css') }}">
    <script src="{{ config('logviewer.blade.jquery-slim') }}"></script>
    <style>
        body {
            padding: 25px;
        }

        h1 {
            font-size: 1.5em;
            margin-top: 0;
        }

        #table-log {
            font-size: 0.85rem;
        }

        .sidebar {
            font-size: 0.85rem;
            line-height: 1;
        }

        .btn {
            font-size: 0.7rem;
        }

        .stack {
            font-size: 0.85em;
        }

        .date {
            min-width: 75px;
        }

        .text {
            word-break: break-all;
        }

        a.llv-active {
            z-index: 2;
            background-color: #f5f5f5;
            border-color: #777;
        }

        .list-group-item {
            word-wrap: break-word;
        }

        .folder {
            padding-top: 15px;
        }

        .div-scroll {
            height: 80vh;
            overflow: hidden auto;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col sidebar mb-3">
            <h1><span class="fa fa-bug" aria-hidden="true"></span> Lumen Log Viewer</h1>
            <p class="text-muted"><i>Origin by Rap2h, modified by Max Sky</i></p>
            <div class="list-group div-scroll">
                @foreach($folders as $folder)
                    <div class="list-group-item">
                        <a href="?f={{ encrypt($folder) }}"><span class="fa fa-folder"></span>{{$folder}}</a>
                        @if ($current_folder == $folder)
                            <div class="list-group folder">
                                @foreach($folder_files as $file)
                                    <a href="?l={{ encrypt($file) }}&f={{ encrypt($folder) }}"
                                       class="list-group-item @if ($current_file == $file) llv-active @endif">{{$file}}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                @foreach($files as $file)
                    <a href="?l={{ encrypt($file) }}"
                       class="list-group-item @if ($current_file == $file) llv-active @endif">
                        {{$file}}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-10 table-container">
            @if ($logs === null)
                <div>
                    Log file >50M, please download it.
                </div>
            @else
                <table id="table-log" class="table table-striped table-hover" data-ordering-index="{{ $standardFormat ? 2 : 0 }}">
                    <thead>
                    <tr>
                        @if ($standardFormat)
                            <th>Level</th>
                            <th>Context</th>
                            <th>Date</th>
                        @else
                            <th>Line number</th>
                        @endif
                        <th>Content</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($logs as $key => $log)
                        <tr data-display="stack{{{$key}}}">
                            @if ($standardFormat)
                                <td class="nowrap text-{{{$log['level_class']}}}">
                                    <span class="fa fa-{{{$log['level_img']}}}"
                                          aria-hidden="true"></span>&nbsp;&nbsp;{{$log['level']}}
                                </td>
                                <td class="text">{{$log['context']}}</td>
                            @endif
                            <td class="date">{{{$log['date']}}}</td>
                            <td class="text">
                                {{{$log['text']}}}
                                @if (isset($log['in_file']))
                                    <br/>{{{$log['in_file']}}}
                                @endif
                                @if ($log['stack'])
                                    <button type="button"
                                            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                            data-display="stack{{{$key}}}">
                                        <span class="fa fa-expand"></span>
                                    </button>
                                    <div class="stack" id="stack{{{$key}}}"
                                         style="display: none; white-space: pre-wrap;">{{{ trim($log['stack']) }}}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            @endif
            <div class="p-3">
                @if($current_file)
                    <a href="?dl={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
                        <span class="fa fa-download"></span> Download file
                    </a>
                    -
                    <a id="clean-log"
                       href="?clean={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
                        <span class="fa fa-sync"></span> Clean file
                    </a>
                    -
                    <a id="delete-log"
                       href="?del={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
                        <span class="fa fa-trash"></span> Delete file
                    </a>
                    @if(count($files) > 1)
                        -
                        <a id="delete-all-log"
                           href="?delall=true{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
                            <span class="fa fa-trash-alt"></span> Delete all files
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
<script src="{{ config('logviewer.blade.dataTables.js') }}"></script>
<script src="{{ config('logviewer.blade.font-awesome') }}"></script>
<script src="{{ config('logviewer.blade.bootstrap.js') }}"></script>
<script src="{{ config('logviewer.blade.dataTables.bootstrap-js') }}"></script>
<script>
    $(function () {
        var table = $('#table-log');
        table.DataTable({
            pagingType: 'full_numbers',
            searching: true,
            stateSave: true,
            order: [table.data('orderingIndex'), 'desc'],
            columnDefs: [{orderable: false, targets: [3]}],
            stateSaveCallback: function (settings, data) {
                localStorage.setItem('datatable', JSON.stringify(data));
            },
            stateLoadCallback: function () {
                var data = JSON.parse(localStorage.getItem('datatable'));
                if (data) {
                    data.start = 0;
                }
                return data;
            }
        });

        $('.table-container tr button').click(function () {
            var span = $(this).children();
            if (span.hasClass('fa-expand')) {
                span.attr('class', 'fa fa-compress');
            } else {
                span.attr('class', 'fa fa-expand');
            }
            $('#' + $(this).data('display')).toggle();
        });

        $('#delete-log, #clean-log, #delete-all-log').click(function () {
            return confirm('Are you sure?');
        });
    });
</script>
</body>
</html>
