@extends('translations::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('translations.name') !!}</p>
@endsection
