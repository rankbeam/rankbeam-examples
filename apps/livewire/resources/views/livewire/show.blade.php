<div>
    <article>
        <h1 data-testid="page-title">{{ $model->getSEOTitle() }}</h1>
        @if ($model->getSEODescription())
            <p data-testid="page-excerpt">{{ $model->getSEODescription() }}</p>
        @endif
    </article>
</div>
