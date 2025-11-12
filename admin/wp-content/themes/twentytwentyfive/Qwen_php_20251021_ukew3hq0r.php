<?php
/*
Template Name: 工大祭実行委員専用ダッシュボード（しおり付き）
*/



get_header();
?>

<style>
.koudaisai-admin {
  max-width: 900px;
  margin: 30px auto;
  padding: 20px;
  font-family: 'Noto Sans JP', sans-serif;
}
.koudaisai-admin h1 {
  text-align: center;
  color: #c00;
  margin-bottom: 25px;
  font-size: 28px;
}
.koudaisai-admin .intro {
  text-align: center;
  margin-bottom: 30px;
  color: #555;
  line-height: 1.6;
}
.koudaisai-admin .card-grid {
  display: grid;
  gap: 20px;
  grid-template-columns: 1fr;
  margin-bottom: 30px;
}
@media (min-width: 600px) {
  .koudaisai-admin .card-grid {
    grid-template-columns: 1fr 1fr;
  }
}
.koudaisai-admin .card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: transform 0.2s;
  text-align: center;
  text-decoration: none;
  color: #333;
}
.koudaisai-admin .card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}
.koudaisai-admin .card h3 {
  margin: 16px 0 10px;
  color: #c00;
}
.koudaisai-admin .card p {
  color: #666;
  font-size: 14px;
}

/* PDF Viewer */
.koudaisai-admin .pdf-section {
  margin-top: 40px;
  background: #f9f9f9;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.koudaisai-admin .pdf-section h2 {
  color: #333;
  margin-bottom: 15px;
  font-size: 20px;
}
.koudaisai-admin .pdf-embed {
  width: 100%;
  height: 600px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
}
.koudaisai-admin .footer-links {
  margin-top: 30px;
  text-align: center;
  font-size: 14px;
  color: #777;
}
.koudaisai-admin .footer-links a {
  color: #c00;
  text-decoration: none;
  margin: 0 10px;
}
.koudaisai-admin .footer-links a:hover {
  text-decoration: underline;
}
</style>

<div class="koudaisai-admin">
  <h1>🎉 工大祭実行委員専用ダッシュボード</h1>

  <p class="intro">
    アナウンス・イベントの編集や、当日使用する「しおり」を確認できます。
  </p>

  <div class="card-grid">
    <a href="https://www.koudaisai.com/admin/?page_id=19" class="card">
      <div class="card-icon">📢</div>
      <h3>アナウンス編集</h3>
      <p>放送原稿・注意喚起などを管理</p>
    </a>

    <a href="https://www.koudaisai.com/admin/?page_id=2" class="card">
      <div class="card-icon">🗓️</div>
      <h3>イベント編集</h3>
      <p>ステージ・模擬店スケジュール編集</p>
    </a>
  </div>

  <!-- PDF Viewer Section -->
  <div class="pdf-section">
    <h2>📖 工大祭 しおり（PDF）</h2>
    <embed
      src="<?php echo esc_url(get_template_directory_uri() . '/siori.pdf'); ?>"
      type="application/pdf"
      class="pdf-embed"
      onerror="this.style.display='none'; this.parentElement.innerHTML+='<p style=\'color:#c00;text-align:center;\'>しおり.pdf が見つかりません。テーマフォルダ内にアップロードしてください。</p>'"
    >
  </embed>
  </div>

  <div class="footer-links">
    <a href="<?php echo esc_url(home_url('/')); ?>">🏠 工大祭トップへ</a> |
    <a href="<?php echo wp_logout_url(home_url('/')); ?>">🚪 ログアウト</a>
  </div>
</div>

<?php
get_footer();
?>