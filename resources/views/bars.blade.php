{{--
    Promo Bar's markup. Publish it to change it (php please vendor:publish
    --tag=promo-bar-views), keeping the data-promo-bar-* attributes: the
    script reads them.
--}}
<section id="{{ $id }}" class="promo-bar promo-bar--{{ $mode }}{{ $class ? ' '.$class : '' }}" aria-label="{{ $label }}" data-promo-bar-group data-mode="{{ $mode }}" data-interval="{{ $interval }}" data-audience-cookie="{{ $cookie }}"{!! $sticky ? ' data-sticky' : '' !!}>
@foreach ($bars as $index => $bar)
@php
    $inactive = $mode === 'rotate' && $index > 0;
    $hidden = ($mode === 'first' && $index > 0) || $bar['audience'];
    $state = $bar['free_delivery']['state'] ?? null;
    $ctaClasses = 'promo-bar__cta'.($plausible ? ' '.$plausible['event'].' plausible-event-bar='.str_replace(' ', '+', $bar['name']) : '');
@endphp
    <div class="{{ $bar['classes'] }}{{ $inactive ? ' is-inactive' : '' }}" data-promo-bar data-promo-bar-id="{{ $bar['id'] }}" data-promo-bar-name="{{ $bar['name'] }}" data-promo-bar-key="{{ $bar['key'] }}"@if ($bar['ends_iso']) data-ends="{{ $bar['ends_iso'] }}"@endif{!! $bar['audience'] ? ' data-audience="'.e($bar['audience']).'"' : '' !!}{!! $bar['groups'] ? ' data-groups="'.e($bar['groups']).'"' : '' !!}{!! $bar['dismiss_days'] ? ' data-dismiss-days="'.(int) $bar['dismiss_days'].'"' : '' !!}{!! $inactive ? ' inert aria-hidden="true"' : '' !!}{!! $hidden ? ' hidden' : '' !!}>
@if ($mode === 'rotate')
        <div class="promo-bar__controls" data-promo-bar-js="controls" hidden>
            <button type="button" class="promo-bar__control" data-promo-bar-previous aria-label="{{ $labels['previous'] }}"><svg aria-hidden="true" viewBox="0 0 16 16"><path d="M10.6 2.6 12 4l-4 4 4 4-1.4 1.4L5.2 8z"/></svg></button>
            <button type="button" class="promo-bar__control" data-promo-bar-pause aria-label="{{ $labels['pause'] }}" data-pause-label="{{ $labels['pause'] }}" data-play-label="{{ $labels['play'] }}"><svg aria-hidden="true" viewBox="0 0 16 16" data-promo-bar-icon="pause"><path d="M4 3h3v10H4zm5 0h3v10H9z"/></svg><svg aria-hidden="true" viewBox="0 0 16 16" data-promo-bar-icon="play" hidden><path d="M4 2.5v11L13 8z"/></svg></button>
            <button type="button" class="promo-bar__control" data-promo-bar-next aria-label="{{ $labels['next'] }}"><svg aria-hidden="true" viewBox="0 0 16 16"><path d="M5.4 2.6 4 4l4 4-4 4 1.4 1.4L10.8 8z"/></svg></button>
        </div>
@endif
        <div class="promo-bar__inner">
            <div class="promo-bar__message" data-promo-bar-message{!! $state ? ' hidden' : '' !!}>{!! $bar['message'] !!}</div>
@if ($bar['countdown'])
            <p class="promo-bar__countdown" data-promo-bar-countdown data-ends="{{ $bar['countdown']['ends_iso'] }}">
                <span data-promo-bar-js="countdown" hidden>{{ $bar['countdown']['label'] }} <span data-promo-bar-remaining aria-hidden="true"></span></span>
                <span data-promo-bar-fallback>{{ $bar['countdown']['ends_label'] }} <time datetime="{{ $bar['countdown']['ends_iso'] }}">{{ $bar['countdown']['ends_text'] }}</time></span>
            </p>
@endif
@if ($bar['free_delivery'])
            <p class="promo-bar__progress" data-promo-bar-free-delivery data-progress-text="{{ $bar['free_delivery']['progress_text'] }}" data-unlocked-text="{{ $bar['free_delivery']['unlocked_text'] }}"{!! $state ? '' : ' data-fetch hidden' !!}>
                <span data-promo-bar-progress-text>{{ $state['text'] ?? '' }}</span>
                <progress class="promo-bar__meter" max="100" value="{{ $state['percent'] ?? 0 }}" aria-hidden="true"></progress>
            </p>
@endif
@if ($bar['code'])
            <p class="promo-bar__code">
                <span>{{ $bar['code']['label'] }}</span>
                <code data-promo-bar-code>{{ $bar['code']['code'] }}</code>
                <button type="button" class="promo-bar__copy" data-promo-bar-js="copy" data-promo-bar-copy data-copy-label="{{ $bar['code']['copy_label'] }}" data-copied-label="{{ $bar['code']['copied_label'] }}" hidden>{{ $bar['code']['copy_label'] }}</button>
            </p>
@endif
@if ($bar['cta'])
            <a class="{{ $ctaClasses }}" href="{{ $bar['cta']['url'] }}" data-promo-bar-cta{!! $bar['cta']['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $bar['cta']['label'] }}@if ($bar['cta']['new_tab'])<span class="promo-bar__visually-hidden"> ({{ __('opens in a new tab') }})</span>@endif</a>
@endif
        </div>
@if ($bar['dismissible'])
        <button type="button" class="promo-bar__dismiss" data-promo-bar-js="dismiss" data-promo-bar-dismiss aria-label="{{ $bar['dismiss_label'] }}" hidden><svg aria-hidden="true" viewBox="0 0 16 16"><path d="M3.7 2.3 8 6.6l4.3-4.3 1.4 1.4L9.4 8l4.3 4.3-1.4 1.4L8 9.4l-4.3 4.3-1.4-1.4L6.6 8 2.3 3.7z"/></svg></button>
@endif
        <span class="promo-bar__visually-hidden" aria-live="polite" data-promo-bar-status></span>
    </div>
@endforeach
</section>
