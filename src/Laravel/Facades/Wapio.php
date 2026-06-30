<?php

declare(strict_types=1);

namespace Wapio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Wapio\Http\Response;

/**
 * Laravel facade for the public Wapio client.
 *
 * @method static Response send(array $payload, array $options = [])
 * @method static Response sendText(array $payload, array $options = [])
 * @method static Response sendImage(array $payload, array $options = [])
 * @method static Response sendVideo(array $payload, array $options = [])
 * @method static Response sendDocument(array $payload, array $options = [])
 * @method static Response sendAudio(array $payload, array $options = [])
 * @method static Response sendSticker(array $payload, array $options = [])
 * @method static Response sendContact(array $payload, array $options = [])
 * @method static Response sendLocation(array $payload, array $options = [])
 * @method static Response sendPoll(array $payload, array $options = [])
 * @method static Response sendQuotedMessage(array $payload, array $options = [])
 * @method static Response sendViewOnceMessage(array $payload, array $options = [])
 * @method static Response sendChannelMessage(string $channelId, string $text, array $options = [])
 * @method static Response editMessage(string $messageId, string $text, array $options = [])
 * @method static Response deleteMessage(string $messageId, array $options = [])
 * @method static Response getMessageInfo(string $messageId, array $options = [])
 * @method static Response resendFailedMessage(string $messageId, array $options = [])
 * @method static Response markMessageAsRead(array $messageIds, ?string $chatJid = null, ?string $sessionId = null, array $options = [])
 * @method static Response sendPresenceUpdate(array $payload, array $options = [])
 * @method static Response getMessageLogs(string $sessionId, array $params = [], array $options = [])
 * @method static Response getSessionLogs(string $sessionId, array $params = [], array $options = [])
 * @method static Response checkIfOnWhatsapp(string $phone, array $options = [])
 * @method static Response listSessions(array $params = [], array $options = [])
 * @method static Response getSession(string $sessionId, array $options = [])
 * @method static Response createSession(array $payload, array $options = [])
 * @method static Response updateSession(string $sessionId, array $payload, array $options = [])
 * @method static Response deleteSession(string $sessionId, array $options = [])
 * @method static Response connectSession(string $sessionId, array $options = [])
 * @method static Response disconnectSession(string $sessionId, array $options = [])
 * @method static Response getSessionQrCode(string $sessionId, array $options = [])
 * @method static Response regenerateApiKey(string $sessionId, array $options = [])
 * @method static Response getSessionStatus(array $options = [])
 * @method static Response getContacts(array $options = [])
 * @method static Response createOrUpdateContact(array $payload, array $options = [])
 * @method static Response getContact(string $phone, array $options = [])
 * @method static Response getContactProfilePicture(string $jid, array $options = [])
 * @method static Response blockContact(string $phone, array $options = [])
 * @method static Response unblockContact(string $phone, array $options = [])
 * @method static Response getGroups(array $options = [])
 * @method static Response getGroupMetadata(string $jid, array $options = [])
 * @method static Response getGroupParticipants(string $jid, array $options = [])
 * @method static Response getGroupProfilePicture(string $groupJid, array $options = [])
 * @method static Response createGroup(array $payload, array $options = [])
 * @method static Response updateGroupSettings(string $groupJid, array $settings, array $options = [])
 * @method static Response addGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response removeGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response promoteGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response demoteGroupParticipants(string $groupJid, array $participants, array $options = [])
 * @method static Response getGroupInviteLink(string $groupJid, array $options = [])
 * @method static Response getGroupInviteMetadata(string $inviteCode, array $options = [])
 * @method static Response acceptGroupInvite(string $inviteCode, ?string $sessionId = null, array $options = [])
 * @method static Response leaveGroup(string $groupJid, ?string $sessionId = null, array $options = [])
 * @method static Response sendGroupMessage(string $groupJid, string $text, array $mentions = [], array $options = [])
 * @method static Response decryptMedia(array $payload, array $options = [])
 * @method static Response onWhatsapp(string $phone, array $options = [])
 * @method static Response getUser(array $options = [])
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
