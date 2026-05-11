<?php

declare(strict_types=1);

namespace Wapio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Wapio\Http\Response;

/**
 * Laravel facade for the Wapio client.
 *
 * Auto-aliased to `Wapio` (root namespace) via composer.json's
 * `extra.laravel.aliases`. Use either form:
 *
 *     use Wapio\Laravel\Facades\Wapio;
 *     // or aliased:
 *     use Wapio;
 *
 *     Wapio::sendText(['to' => '+15551234567', 'text' => 'Hi!']);
 *
 * @method static Response send(array $payload, array $options = [])
 * @method static Response sendText(array $payload, array $options = [])
 * @method static Response sendImage(array $payload, array $options = [])
 * @method static Response sendVideo(array $payload, array $options = [])
 * @method static Response sendAudio(array $payload, array $options = [])
 * @method static Response sendDocument(array $payload, array $options = [])
 * @method static Response sendSticker(array $payload, array $options = [])
 * @method static Response sendLocation(array $payload, array $options = [])
 * @method static Response sendContact(array $payload, array $options = [])
 * @method static Response sendPoll(array $payload, array $options = [])
 * @method static Response sendMessageWithMentions(array $payload, array $options = [])
 * @method static Response sendQuotedMessage(array $payload, array $options = [])
 * @method static Response editMessage(string $messageId, string $text, array $options = [])
 * @method static Response deleteMessage(string $messageId, array $options = [])
 * @method static Response resendMessage(string $messageId, array $options = [])
 * @method static Response getMessageInfo(string $messageId, array $options = [])
 * @method static Response markMessagesRead(array $messageIds, ?string $chatJid = null, array $options = [])
 * @method static Response sendPresenceUpdate(string $to, string $presence, array $options = [])
 * @method static Response checkIfOnWhatsapp(string $phone, array $options = [])
 * @method static Response listSessions(array $params = [], array $options = [])
 * @method static Response getAllWhatsAppSessions(array $params = [], array $options = [])
 * @method static Response getSession(string $sessionId, array $options = [])
 * @method static Response getWhatsAppSessionDetails(string $sessionId, array $options = [])
 * @method static Response createSession(array $payload, array $options = [])
 * @method static Response createWhatsAppSession(array $payload, array $options = [])
 * @method static Response updateSession(string $sessionId, array $payload, array $options = [])
 * @method static Response updateWhatsAppSession(string $sessionId, array $payload, array $options = [])
 * @method static Response deleteSession(string $sessionId, array $options = [])
 * @method static Response deleteWhatsAppSession(string $sessionId, array $options = [])
 * @method static Response connectSession(string $sessionId, array $options = [])
 * @method static Response connectWhatsAppSession(string $sessionId, array $options = [])
 * @method static Response disconnectSession(string $sessionId, array $options = [])
 * @method static Response disconnectWhatsAppSession(string $sessionId, array $options = [])
 * @method static Response restartSession(string $sessionId, array $options = [])
 * @method static Response getSessionQrCode(string $sessionId, array $options = [])
 * @method static Response getWhatsAppSessionQrCode(string $sessionId, array $options = [])
 * @method static Response regenerateApiKey(string $sessionId, array $options = [])
 * @method static Response getSessionStatus(array $options = [])
 * @method static Response getSessionUserInfo(array $options = [])
 * @method static Response getSessionSettings(string $sessionId, array $options = [])
 * @method static Response updateSessionSettings(string $sessionId, array $settings, array $options = [])
 * @method static Response upsertWebhookConfig(string $sessionId, array $config, array $options = [])
 * @method static Response getWebhookConfig(string $sessionId, array $options = [])
 * @method static Response deleteWebhookConfig(string $sessionId, array $options = [])
 * @method static Response upsertProxyConfig(string $sessionId, array $config, array $options = [])
 * @method static Response getProxyConfig(string $sessionId, array $options = [])
 * @method static Response deleteProxyConfig(string $sessionId, array $options = [])
 * @method static Response listMessageLogs(string $sessionId, array $params = [], array $options = [])
 * @method static Response listSessionLogs(string $sessionId, array $params = [], array $options = [])
 * @method static Response listWebhookDeliveries(string $sessionId, array $params = [], array $options = [])
 * @method static Response getContacts(array $options = [])
 * @method static Response getContact(string $phone, array $options = [])
 * @method static Response getContactInfo(string $phone, array $options = [])
 * @method static Response getContactProfilePicture(string $jid, array $options = [])
 * @method static Response blockContact(string $phone, array $options = [])
 * @method static Response unblockContact(string $phone, array $options = [])
 * @method static Response upsertContact(array $payload, array $options = [])
 * @method static Response getGroups(array $options = [])
 * @method static Response getGroup(string $jid, array $options = [])
 * @method static Response getGroupMetadata(string $jid, array $options = [])
 * @method static Response getGroupParticipants(string $jid, array $options = [])
 * @method static Response getGroupProfilePicture(string $groupJid, array $options = [])
 * @method static Response getGroupInviteLink(string $groupJid, array $options = [])
 * @method static Response getGroupInviteInfo(string $inviteCode, array $options = [])
 * @method static Response acceptGroupInvite(string $inviteCode, array $options = [])
 * @method static Response createGroup(array $payload, array $options = [])
 * @method static Response leaveGroup(string $groupJid, array $options = [])
 * @method static Response updateGroupSettings(string $groupJid, array $settings, array $options = [])
 * @method static Response addGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response removeGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response promoteGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response demoteGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response updateGroupParticipants(string $groupJid, string $action, array $participants, array $options = [])
 * @method static Response getChats(array $options = [])
 * @method static Response getChat(string $chatJid, array $options = [])
 * @method static Response markChatRead(string $chatJid, ?array $messageIds = null, array $options = [])
 * @method static Response uploadMediaGrant(array $payload, array $options = [])
 * @method static Response uploadMediaFile(array $payload, array $options = [])
 * @method static Response getMediaDownloadUrl(string $mediaId, array $options = [])
 * @method static Response decryptMedia(array $payload, array $options = [])
 * @method static Response decryptMediaFile(array $payload, array $options = [])
 * @method static Response directUpload(array $payload, array $options = [])
 * @method static Response onWhatsapp(string $phone, array $options = [])
 * @method static Response pnFromLid(string $lid, array $options = [])
 * @method static Response lidFromPn(string $pn, array $options = [])
 * @method static Response getUser(array $options = [])
 * @method static Response getOperationStatus(string $operationId, array $options = [])
 * @method static Response getDashboardOverview(array $options = [])
 *
 * @see \Wapio\Wapio
 */
class Wapio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wapio\Wapio::class;
    }
}
