<label for="{{ $id }}" class="form-label">
  {{ $label }} @if ($required) <span class="text-danger">*</span> @endif
</label>
<select name="{{ $name }}" id="{{ $id }}" class="{{ $class }} @error($name) is-invalid @enderror" @if ($required) required @endif>
  @if ($placeholder !== null)
    <option value="">{{ $placeholder }}</option>
  @endif
  @foreach ($clinicas as $clinica)
    <option value="{{ $clinica->id }}" @selected(old($name, $valor) == $clinica->id)>{{ $clinica->nome }}</option>
  @endforeach
</select>
@error($name)
  <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
