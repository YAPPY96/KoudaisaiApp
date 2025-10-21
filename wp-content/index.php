<?php
// Silence is golden.
function display_events_shortcode() {
    // HTMLの出力を一時的にためるバッファを開始
    ob_start();

    // イベント投稿を取得するためのクエリ
    $args = array(
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'meta_key'       => 'date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC'
    );

    $events_query = new WP_Query($args);

    // ここから下は、前回と同じ表示用のHTMLとCSSです
    ?>
    <style>
    .event-list-container { max-width: 900px; margin: 20px auto; padding: 20px; font-family: sans-serif; }
    .event-item { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 25px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); background: #fff; }
    .event-item h2 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 1.8em; }
    .event-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
    .event-meta-item { background: #f9f9f9; padding: 15px; border-radius: 5px; }
    .event-meta-item strong { display: block; margin-bottom: 5px; color: #333; }
    .event-description, .event-caution, .event-others { margin-top: 15px; line-height: 1.6; }
    .reservation-slots { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
    .reservation-slots ul { list-style: none; padding: 0; }
    .reservation-slots li { display: flex; justify-content: space-between; padding: 8px 12px; border-radius: 4px; margin-bottom: 5px; }
    .reservation-slots li:nth-child(odd) { background: #f7f7f7; }
    .status-full { font-weight: bold; color: #d9534f; }
    .status-few_left { font-weight: bold; color: #f0ad4e; }
    .status-available { font-weight: bold; color: #5cb85c; }
    </style>

    <div class="event-list-container">
        <?php if ($events_query->have_posts()) : ?>
            <?php while ($events_query->have_posts()) : $events_query->the_post();
                $buildingName = get_field('buildingname');
                $eventName = get_field('eventname') ?: get_the_title();
                $time = get_field('time');
                $description = get_field('description');
                $date = get_field('date');
                $groupName = get_field('groupname');
                $reservation = get_field('reservation');
                $caution = get_field('caution');
                $others = get_field('others');
                $reservationSlots = get_field('reservationslots');
            ?>
            <article id="post-<?php the_ID(); ?>" class="event-item">
                <h2><?php echo esc_html($eventName); ?></h2>
                <div class="event-meta">
                    <div class="event-meta-item"><strong><span role="img" aria-label="日付">📅</span> 日付</strong> <?php echo esc_html($date); ?></div>
                    <div class="event-meta-item"><strong><span role="img" aria-label="時間">🕒</span> 時間</strong> <?php echo esc_html($time); ?></div>
                    <div class="event-meta-item"><strong><span role="img" aria-label="場所">📍</span> 場所</strong> <?php echo esc_html($buildingName); ?></div>
                    <?php if ($groupName) : ?>
                    <div class="event-meta-item"><strong><span role="img" aria-label="団体名">👥</span> 団体名</strong> <?php echo esc_html($groupName); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($description) : ?><div class="event-description"><h3>イベント内容</h3><p><?php echo nl2br(esc_html($description)); ?></p></div><?php endif; ?>
                <?php if ($reservation && $reservationSlots) : ?>
                <div class="reservation-slots">
                    <h3>予約状況</h3>
                    <ul>
                        <?php foreach ($reservationSlots as $slot) : 
                            $status_class = 'status-' . esc_attr($slot['status']);
                            $status_text = '';
                            switch ($slot['status']) {
                                case 'full': $status_text = '満席'; break;
                                case 'few_left': $status_text = '残りわずか'; break;
                                case 'available': $status_text = '空きあり'; break;
                            }
                        ?>
                        <li>
                            <span><?php echo esc_html($slot['time']); ?></span>
                            <span class="<?php echo $status_class; ?>"><?php echo esc_html($status_text); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($caution) : ?><div class="event-caution"><h4>注意事項</h4><p><?php echo nl2br(esc_html($caution)); ?></p></div><?php endif; ?>
                <?php if ($others) : ?><div class="event-others"><h4>その他</h4><p><?php echo nl2br(esc_html($others)); ?></p></div><?php endif; ?>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <p>現在、表示できるイベントはありません。</p>
        <?php endif; ?>
    </div>
    <?php
    // バッファの内容を取得してバッファをクリーンにする
    return ob_get_clean();
}
// [display_events] というショートコードを登録する
add_shortcode('display_events', 'display_events_shortcode');