<div id="{{ $id }}" {{ $attributes }}></div>
@once
<script src="{{ $scriptUrl }}"></script>
@endonce
<script>
    window.vexpayCheckouts = window.vexpayCheckouts || {};
    window.vexpayCheckouts[@json($id)] = window.VexPay.checkout(@json($options())).mount(@json('#' . $id));
</script>
