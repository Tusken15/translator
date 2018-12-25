@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="text-left mb-2 col-md-7">
                    <div class="alert alert-success">
                        <div id="translate-info"></div>
                        <ul class="mb-0" >
                            <li>Known: {{$known}}</li>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="text-right mb-2 col-md-5">
                    <form enctype="multipart/form-data" id="import" action="/import-file" method="post">
                        @csrf
                        <input type="file" name="file" onchange="$('#import').submit();" class="btn btn-primary" value="Import"/>
                    </form>
                </div>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <td>Word</td>
                            <td>Translation</td>
                            <td>Found</td>
                            <td>Frequency</td>
                            <td>Known</td>
                            <td>Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($words AS $word) 
                            <tr id="row-{{$word->ID}}">
                                <td>{{$word->word}}</td>
                                <td>
                                    <input type="text" class="table-input" value="{{$word->translation}}" onchange="changeTranslation(this, {{$word->ID}})"/>
                                </td>
                                <td>{{$word->count_words}}</td>
                                <td>{{round(($word->count_words / $total_words)*1000,4)."‰"}}</td>
                                <td class="known known-{{$word->ID}}"  onclick="setKnown({{$word->ID}})">
                                    <span class='{{$word->known}}'></span>
                                </td>
                                <td>
                                    <a class="c-red" href="javascript:void(0)" onclick="deleteWord({{$word->ID}})"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--
<div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
    
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Import text</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <textarea id="input"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" onclick="importText()">Import</button>
        </div>
      </div>
      
    </div>
  </div>

<meta name="csrf-token" content="{{ csrf_token() }}" />
-->

<link href="/css/styles.css" rel="stylesheet" type="text/css">
<script src="{{asset('js/translator.js')}}"></script>
@endsection
