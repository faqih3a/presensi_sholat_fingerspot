@props([
    'label' => null,
    'name',
    'selected' => '',
    'options' => [],
    'formId',
    'buttonClass' => 'btn btn-sm btn-white border dropdown-toggle fw-semibold px-3 py-2 d-flex align-items-center gap-2',
    'buttonStyle' => 'border-radius: 0.75rem; min-width: 130px; background: #fff;',
    'dropdownAlign' => 'start'
])

<div class="d-flex align-items-center gap-2">
    @if($label)
        <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">{{ $label }}</label>
    @endif
    <div class="dropdown">
        <!-- Hidden input inside the form so it is sent on form submit -->
        <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
        
        <button class="{{ $buttonClass }}" 
                type="button" 
                id="{{ $name }}Dropdown" 
                data-bs-toggle="dropdown" 
                aria-expanded="false" 
                style="{{ $buttonStyle }}">
            <span>{{ $options[$selected] ?? ($options[''] ?? 'Semua') }}</span>
            <i class="bi bi-chevron-down small ms-auto text-muted"></i>
        </button>
        <ul class="dropdown-menu {{ $dropdownAlign === 'end' ? 'dropdown-menu-end' : '' }} shadow-lg border-0" aria-labelledby="{{ $name }}Dropdown" style="border-radius: 1rem; padding: 0.5rem; margin-top: 10px;">
            @foreach($options as $val => $text)
                @if($val === '')
                    <li><a class="dropdown-item py-2 {{ $selected == '' ? 'active' : '' }}" href="javascript:void(0)" onclick="updateComponentFilter('{{ $name }}', '', '{{ $formId }}')">{{ $text }}</a></li>
                    @if(!$loop->last)
                        <li><hr class="dropdown-divider mx-2"></li>
                    @endif
                @else
                    <li><a class="dropdown-item py-2 {{ $selected == $val ? 'active' : '' }}" href="javascript:void(0)" onclick="updateComponentFilter('{{ $name }}', '{{ $val }}', '{{ $formId }}')">{{ $text }}</a></li>
                @endif
            @endforeach
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
    function updateComponentFilter(name, value, formId) {
        const form = document.getElementById(formId);
        if (form) {
            const input = form.querySelector('input[name="' + name + '"]');
            if (input) {
                input.value = value;
            }
            form.submit();
        }
    }
</script>
@endpush
@endonce
