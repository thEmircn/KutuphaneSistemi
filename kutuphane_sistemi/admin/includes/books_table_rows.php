<?php foreach ($books as $book): ?>
<tr>
    <td>
        <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
            <img data-src="../uploads/books/<?= $book['fotograf'] ?>" 
                 src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                 width="50" height="60" class="img-thumbnail lazy-image">
        <?php else: ?>
            <div class="bg-light text-center d-flex align-items-center justify-content-center" style="width: 50px; height: 60px; font-size: 20px;">
                📚
            </div>
        <?php endif; ?>
    </td>
    <td>
        <a href="book_detail.php?id=<?= $book['id'] ?>" class="text-decoration-none">
            <strong><?= htmlspecialchars($book['kitap_adi']) ?></strong>
        </a>
        <br><small class="text-muted"><?= date('d.m.Y', strtotime($book['eklenme_tarihi'])) ?></small>
    </td>
    <td><?= htmlspecialchars($book['yazar_adi'] ?? '-') ?></td>
    <td><?= htmlspecialchars($book['kategori_adi'] ?? '-') ?></td>
    <td><small><?= htmlspecialchars($book['isbn'] ?? '-') ?></small></td>
    <td><span class="badge bg-secondary"><?= $book['stok_adedi'] ?></span></td>
    <td><span class="badge bg-primary"><?= $book['mevcut_adet'] ?></span></td>
    <td>
        <?php if ($book['durum'] == 'mevcut'): ?>
            <span class="badge bg-success">Mevcut</span>
            <?php elseif ($book['durum'] == 'tukendi'): ?>
           <span class="badge bg-warning">Tükendi</span>
       <?php else: ?>
           <span class="badge bg-secondary">Pasif</span>
       <?php endif; ?>
   </td>
   <td>
       <div class="btn-group btn-group-sm" role="group">
           <a href="book_detail.php?id=<?= $book['id'] ?>" class="btn btn-outline-info" title="Detay">
               👁️
           </a>
           <button type="button" class="btn btn-outline-warning" onclick="editBook(<?= $book['id'] ?>)" title="Düzenle">
               ✏️
           </button>
           <button type="button" class="btn btn-outline-danger" onclick="deleteBook(<?= $book['id'] ?>)" title="Sil">
               🗑️
           </button>
       </div>
   </td>
</tr>
<?php endforeach; ?>