<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes</title>
</head>
<body>
    <h1>Notes</h1>
    <form action="/" method="POST">
        <input type="hidden" name="action" value="add_note">
        <input type="text" name="title" placeholder="Title" required>
        <textarea name="content" placeholder="Content" required></textarea>
        <button type="submit">Add Note</button>
    </form>
    <ul>
        <?php var_dump($notes); foreach ($notes as $note): ?>
            <li>
                <h2><?= htmlspecialchars($note['title']) ?></h2>
                <p><?= htmlspecialchars($note['content']) ?></p>
                <form action="/" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="edit_note">
                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                    <input type="text" name="title" value="<?= htmlspecialchars($note['title']) ?>" required>
                    <textarea name="content" required><?= htmlspecialchars($note['content']) ?></textarea>
                    <button type="submit">Edit</button>
                </form>
                <form action="/" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_note">
                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
