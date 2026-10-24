@props(['action', 'title' => 'تأكيد الحذف', 'message' => 'هل أنت متأكد من رغبتك في حذف هذا السجل؟'])

<form method="POST" action="{{ $action }}" class="d-inline" onsubmit="return confirm('{{ $message }}');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="{{ $title }}">
        <i class="bi bi-trash3"></i>
    </button>
</form>
