<?php
// activity_logger.php
function log_activity(PDO $pdo, array $data): void {
    $sql = "INSERT INTO activity_logs
      (actor_type, actor_staff_id, actor_username, action, entity_type, entity_id,
       company_name, talent_name_kana, message_ja, ip, user_agent)
      VALUES
      (:actor_type, :actor_staff_id, :actor_username, :action, :entity_type, :entity_id,
       :company_name, :talent_name_kana, :message_ja, :ip, :user_agent)";
    $stmt = $pdo->prepare($sql);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt->execute([
        ':actor_type' => $data['actor_type'] ?? 'staff',
        ':actor_staff_id' => $data['actor_staff_id'] ?? null,
        ':actor_username' => $data['actor_username'] ?? null,
        ':action' => $data['action'],
        ':entity_type' => $data['entity_type'],
        ':entity_id' => $data['entity_id'] ?? null,
        ':company_name' => $data['company_name'] ?? null,
        ':talent_name_kana' => $data['talent_name_kana'] ?? null,
        ':message_ja' => $data['message_ja'],
        ':ip' => $ip,
        ':user_agent' => $ua ? mb_substr($ua, 0, 255) : null
    ]);
}
