@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <td>Word</td>
                            <td>Translation</td>
                            <td>Frequency</td>
                            <td>Known</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($words AS $word) 
                            <tr>
                                <td>{{$word->word}}</td>
                                <td>{{$word->translation}}</td>
                                <td>{{(($word->count_words / $total_words)*100)."%"}}</td>
                                <td>{!!$word->known == 'add' ? "<span class='green'></span>" : "<span class='red'></span>"!!}</td>
                            </tr>
                        @endforeach
                    </tbody>
            
                </table>
            </div>
        </div>
    </div>
</div>
<link href="/css/styles.css" rel="stylesheet" type="text/css">
@endsection
