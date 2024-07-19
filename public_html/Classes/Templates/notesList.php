<!DOCTYPE html>
<html lang="rus">
<head>
    <meta charset="UTF-8">
    <title>Notebook</title>
    <style>
        :root {
            --background-color: #f4f4f4;
            --text-color: #333;
            --textarea-bg: #fff;
            --textarea-text: #000;
            --textarea-border: #ccc;
            --button-bg: #007BFF;
            --button-text: white;
            --button-hover-bg: #0056b3;
            --note-bg: white;
            --note-border: #ddd;
            --note-title: #007BFF;
            --note-content: #333;
            --tags-text: #888;
            --created-at-text: #aaa;
            --tag-bg: #007BFF;
            --tag-text: white;
            --tag-bg-alt: #28a745;
            --tag-text-alt: white;
        }

        [data-theme="dark"] {
            --background-color: #121212;
            --text-color: #ffffff;
            --textarea-bg: #333333;
            --textarea-text: #ffffff;
            --textarea-border: #555555;
            --button-bg: #BB86FC;
            --button-text: #000000;
            --button-hover-bg: #3700B3;
            --note-bg: #1e1e1e;
            --note-border: #555555;
            --note-title: #BB86FC;
            --note-content: #dddddd;
            --tags-text: #aaaaaa;
            --created-at-text: #888888;
            --tag-bg: #BB86FC;
            --tag-text: #000000;
            --tag-bg-alt: #03DAC6;
            --tag-text-alt: #000000;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
            color: var(--text-color);
        }
        h1, h2 {
            color: var(--text-color);
        }
        form {
            margin-bottom: 20px;
        }
        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: var(--textarea-bg);
            color: var(--textarea-text);
            border: 1px solid var(--textarea-border);
        }
        button {
            padding: 10px 20px;
            background-color: var(--button-bg);
            color: var(--button-text);
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: var(--button-hover-bg);
        }
        .notes {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .note {
            display: none; /* Скрываем карточки по умолчанию */
            background-color: var(--note-bg);
            padding: 10px;
            width: calc(20% - 20px);
            min-width: 400px;
            border: 1px solid var(--note-border);
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .note.show {
            display: block;
        }
        .note p {
            margin: 0 0 10px 0;
        }
        .note .title {
            color: var(--note-title);
            font-weight: bold;
        }
        .note .content {
            color: var(--note-content);
            white-space: pre-wrap; /* Ensures that new lines are preserved */
        }
        .note .tags {
            color: var(--tags-text);
            font-size: 0.9em;
        }
        .note .created-at {
            color: var(--created-at-text);
            font-size: 0.8em;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--textarea-border);
            border-radius: 5px;
            background-color: var(--textarea-bg);
            color: var(--textarea-text);
        }
        .theme-toggle {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .tag {
            display: inline-block;
            padding: 3px 7px;
            margin-right: 5px;
            border-radius: 3px;
        }
        .tag-url { background-color: var(--tag-bg); color: var(--tag-text); }
        .tag-bold { background-color: var(--tag-bg-alt); color: var(--tag-text-alt); }
        .tag-italic { background-color: var(--tag-bg); color: var(--tag-text); }
        .tag-code { background-color: var(--tag-bg-alt); color: var(--tag-text-alt); }
        .tag-pre { background-color: var(--tag-bg); color: var(--tag-text); }
        .tag-underline { background-color: var(--tag-bg-alt); color: var(--tag-text-alt); }
        .tag-strikethrough { background-color: var(--tag-bg); color: var(--tag-text); }
        .tag-spoiler { background-color: var(--tag-bg-alt); color: var(--tag-text-alt); }
    </style>
</head>
<body>
    <div class="theme-toggle">
        <label for="theme-toggle-checkbox">Dark Mode</label>
        <input type="checkbox" id="theme-toggle-checkbox">
    </div>
    <h1>Notebook</h1>
    <form action="index.php" method="post">
        <textarea name="note" required></textarea>
        <button type="submit">Add Note</button>
    </form>

    <div class="search-bar">
        <input type="text" id="search" placeholder="Search notes...">
    </div>

    <h2>Your Notes</h2>
    <div class="notes">
        <?php if (!empty($notes)): ?>
            <?php foreach ($notes as $note): ?>
                <div class="note">
                    <p><strong>Title:</strong> <span class="title"><?= htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p><strong>Content:</strong> <span class="content"><?= $note['content'] ?></span></p>
                    <p class="tags"><strong>Tags:</strong> <?= htmlspecialchars($note['tags'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="created-at"><?= htmlspecialchars(date("Y-m-d", strtotime($note['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notes yet!</p>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const theme = getCookie('theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.getElementById('theme-toggle-checkbox').checked = true;
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }

            document.getElementById('theme-toggle-checkbox').addEventListener('change', function() {
                if (this.checked) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    setCookie('theme', 'dark', 365);
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    setCookie('theme', 'light', 365);
                }
            });

            document.getElementById('search').addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();
                const notes = document.querySelectorAll('.note');

                if (searchValue.length >= 3) {
                    notes.forEach(function(note) {
                        const content = note.querySelector('.content').innerHTML.toLowerCase();
                        const title = note.querySelector('.title').innerHTML.toLowerCase();
                        const tags = note.querySelector('.tags').innerHTML.toLowerCase();
                        if (content.includes(searchValue) || title.includes(searchValue) || tags.includes(searchValue)) {
                            note.classList.add('show');
                        } else {
                            note.classList.remove('show');
                        }
                    });
                } else {
                    notes.forEach(function(note) {
                        note.classList.remove('show');
                    });
                }
            });
        });

        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
</body>
</html>
