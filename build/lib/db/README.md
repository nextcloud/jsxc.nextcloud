lib/db
===

This are the Entity and Mappers of the different stanza types.
The entities are used to:
 - store them in the DB. (e.g. the Message entity, the Presence entity)
 - to easily return them to the client via the polling system 
 (e.g. Message entity, Presence entity (these are stored inside the ojsxc_stanza table.)
 - directly return them to te client (e.g. IQRoster entity)
 - parse an incoming stanza to an object (This is currently only done for the 
 incoming Presence stanza.)

The following mappers are used: 
 - StanzaMapper -> parent of all the other mappers
 - MessageMapper -> used to store Message entities inside the longpolling table.
 - PresenceMapper -> used to save, update and fetch presences of the users
 - IQRoster doesn't have a mapper since this won't be saved in the DB.


# Important note on userids and jid's

When users and Stanza's containing users are stored inside the database this must be done using the Nextcloud userid
and not using a jid! So at all times the user 'admin' must be stored as 'admin' and not as 'admin@localhost/internal' even
in the `to` and `from` parameters of raw xml stanzas. This to support multiple domain Nextcloud instances.
The userId's are escaped using the `OCA\OJSXC\AppInfo\Appplication::sanitizeUserId` function to support the XMPP standards.
When the userId is available inside the class the `OJSXC_UserId` paramter of `OCA\OJSXC\AppInfo\Appplication` must be used.