@extends('layouts.app')
@section('content')
  <h3>My CVs</h3>
  <a href="{{ route('resumes.create') }}">Create new</a>
  <table>
    <thead><tr><th>ID</th><th>Name</th><th>Updated</th><th></th></tr></thead>
    <tbody>
      @foreach($resumes as $r)
        <tr>
          <td>{{ $r->id }}</td>
          <td>{{ $r->name }}</td>
          <td>{{ $r->updated_at }}</td>
          <td>
            <a href="{{ route('resumes.edit',$r) }}">Edit</a>
            <a href="{{ route('resumes.preview.xlsx',$r) }}" target="_blank">Preview</a>
            <a href="{{ route('resumes.download.xlsx',$r) }}">XLSX</a>
            <a href="{{ route('resumes.download.pdf',$r) }}">PDF</a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection
