<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости с настроением</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .news-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .mood-selector {
            margin: 20px 0;
        }

        .comparison-box {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">📰 Новости с настроением</h1>

        <div class="mood-selector text-center mb-4">
            <label class="me-2">Выберите настроение:</label>
            <select id="moodSelect" class="form-select d-inline-block w-auto">
                <option value="neutral">😐 Нейтрально</option>
                <option value="joyful">😊 Радостно</option>
                <option value="sad">😢 Грустно</option>
                <option value="ironic">😏 Иронично</option>
                <option value="optimistic">😃 Оптимистично</option>
            </select>
        </div>

        <div class="row" id="newsGrid">
            @foreach($news as $item)
                <div class="col-md-4 mb-4">
                    <div class="card news-card" data-id="{{ $item->id }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $item->title }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($item->content, 100) }}</p>
                            <small class="text-muted">
                                {{ $item->source_name }} • {{ $item->published_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Модальное окно -->
    <div class="modal fade" id="newsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Новость</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>📄 Оригинал</h6>
                            <div class="comparison-box p-3 bg-light" id="originalText"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>✨ Переписано</h6>
                            <div class="comparison-box p-3 bg-light" id="rewrittenText"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            Источник: <a href="#" id="sourceLink" target="_blank">Перейти к оригиналу</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal(document.getElementById('newsModal'));
            const moodSelect = document.getElementById('moodSelect');

            // Клик по карточке
            document.querySelectorAll('.news-card').forEach(card => {
                card.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const mood = moodSelect.value;
                    openNews(id, mood);
                });
            });

            // Смена настроения
            moodSelect.addEventListener('change', function () {
                // Можно обновить открытую новость или просто сохранить значение
            });

            function openNews(id, mood) {
                fetch(`/news/${id}?mood=${mood}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('modalTitle').textContent = 'Новость';
                        document.getElementById('originalText').textContent = data.original;
                        document.getElementById('rewrittenText').textContent = data.rewritten;
                        document.getElementById('sourceLink').href = data.source_url;
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Не удалось загрузить новость');
                    });
            }
        });
    </script>
</body>

</html>