
# Overview

The AGP contains several types of events:

* __Events__  
  Events which are handled inside of a request and distributed to every listener
* __Frontend Events__  
  Events which are triggered by Javascript components
* __Commands__  
  A command is executed at the background and executes a specific logic

# Events

Starting with Kajona v4.5, the way how events are handled was rewritten from scratch. Events may 
be used to react on special actions triggered by the system. For example, it's possible to be 
notified if a record is deleted, if a record is updated or if a user logs into the system for 
the first time.

All events handled by Kajona are identified using a string-based identifier, e.g. 
``core.system.recordupdated``. In order to be notified in case of an event, you have to implement 
the generic interface ``GenericeventListenerInterface``. Compared to a more type-safe event-interface
(e.g. interface_record_deleted_listener", the generic approach reduced the coupling between modules 
and avoids hard-coded dependencies between packages Example: If your faqs module wants to react on 
events triggered by the search, the ``GenericeventListenerInterface`` is all you have to implement.
If the interface was named ``SearchTriggeredInterface`` and the interface is provided by the 
search-package, your faq implementation will fail if the search package is not available (due to an
undefined interface).

## Handling events 
If you want to handle a certain event, you have to provide a listener and register the listener for
this event. Let's say you want to be notified in case a record is deleted in order to write a line
to a logfile. The listener you have to provide would be the following implementation:

```php
<?php

namespace Kajona\Module\System;

use Kajona\System\System\GenericeventListenerInterface;
use Kajona\System\System\Logger;
use Kajona\System\System\CoreEventdispatcher;

class LoggingRecorddeletedlistener implements GenericeventListenerInterface
{
    public function handleEvent($strEventName, array $arrArguments)
    {
        list($strSystemid, $strSourceClass) = $arrArguments;
        Logger::getInstance()->addLogRow("record delete, id: ".$strSystemid, Logger::$levelInfo);
        return true;
    }
}
CoreEventdispatcher::getInstance()->removeAndAddListener("core.system.recorddeleted", new LoggingRecorddeletedlistener());

```

This class takes care of everything. The last lines registeres the listener at the CoreEventdispatcher
for the event identified by ``core.system.recorddeleted``. The listener implements the interface and
drops a line to the logfile.

Kajona scans the filesystems for possible listener at startup, so you don't have to worry that your
handler will be registered. All you have to stick to is placing your listener within the packages
system-directory.

## Throwing events
Throwing an event is a piece of cake! All you have to know is the identifier of an event. Let's keep
to the example above: Let's notify listeners about a deleted record. Normally this event is handled
by Kajona, but let's trigger it again:

```php
<?php

namespace Kajona\Module\System;

use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Objectfactory;

$systemid = "[systemid]";
$recordDeleted = Objectfactory::getInstance()->getObject($systemid);

CoreEventdispatcher::getInstance()->notifyGenericListeners(
    "core.system.recorddeleted",
    array($systemid, get_class($recordDeleted))
);

```

All we do is fetching an instance of the core_eventdispatcher and calling the method 
``notifyGenericListeners``. Arguments to this method are the identifier of the event and an array of 
arguments. This array of arguments will be passed to the registered listeners using the 
callback-method handleEvent.

Thats all. You should now be able to provide and register event-listeners and to throw new events 
based on an identifier.

## Reference

<table>
	<tbody>
		<tr>
			<th>Identifier</th>
			<th colspan="2" style="border-bottom: 1px solid #ccc;">
Description</th>
		</tr>
		<tr>
			<td rowspan="3" style="border-right: 1px solid #ccc; vertical-align: top;">core.system.request.startprocessing</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.6</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>
			string $strModule<br />
			string $strAction<br />
			</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Invoked right before starting to process the current request. The event is triggered&nbsp;by the request-dispatcher right before the request is given over to the controller.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc; vertical-align: top;">core.system.request.endprocessing</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.6</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>
			string $strModule<br />
			string $strAction<br />
			</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Invoked right before finishing to process the current request. The event is triggered&nbsp;by the request-dispatcher right before closing the session and passing back the response object.&nbsp;You may modify the request by accessing the response-object.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc; vertical-align: top;">core.system.request.aftercontentsend</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.6</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>RequestEntrypointEnum $objEntrypoint</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Invoked right after sending the response back to the browser, but before starting to&nbsp;shut down the request.<br />
			This means you are not able to change the response anymore, also the session is already closed to&nbsp;keep other threads from waiting. Use this event to perform internal cleanups if required.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc;border-right: 1px solid #ccc; vertical-align: top;">core.system.recordupdated</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>\Kajona\System\System\Model $objRecord <br />
			bool $bitRecordCreated
			</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown whenever a record is updated to the database.<br/> The param $bitRecordCreated indicates
			if a record was created(true) of if is only being updated(false)</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc;border-right: 1px solid #ccc; vertical-align: top;">core.system.objectassignmentsupdated</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.8</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string[] $arrNewAssignments<br />
string[] $areRemovedAssignments<br />
string[] $areCurrentAssignments<br />
Root $objObject<br />
string $strProperty<br /><br />return bool</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Triggered as soon as a property mapping to objects is updated. Therefore the event is triggered as soon
as assignments are added or removed from an object.
The event gets a list of all three relevant items: assignments added, assignments removed, assignments remaining.
The relevant object and the name of the changed property are passed, too.<br />Return a valid bool value, otherwise the transaction will be rolled back!</td>
		</tr>	
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.recordcopied</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strOldSystemid<br />
			string $strNewSystemid<br />
			\Kajona\System\System\Model $objNewObjectCopy</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Called whenever a record was copied. Event will be fired BEFORE child objects are being copied. Useful to perform additional actions, e.g. update / duplicate foreign assignments.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.recordcopyfinished</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.6</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strOldSystemid<br />
			string $strNewSystemid<br />
			\Kajona\System\System\Model $objNewObjectCopy</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Called whenever a record was copied. Event will be fired AFTER child objects were copied. Useful to perform additional actions, e.g. update / duplicate foreign assignments.</td>
		</tr>
		<tr>
        			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.permissionschanged</td>
        			<td style="border-top: 1px solid #ccc;">Since</td>
        			<td style="border-top: 1px solid #ccc;">4.5</td>
        		</tr>
        		<tr>
        			<td>Arguments</td>
        			<td>string $strSystemid&nbsp;<br />
        			array $arrPermissions&nbsp;</td>
        		</tr>
        		<tr>
        			<td>Description</td>
        			<td>Invoked if the permissions of a record have been changed.
                            Triggered only by the "root" node of the change cascade.</td>
        		</tr>	
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.previdchanged</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strSystemid&nbsp;<br />
			string $strOldPrevId<br />
			string $strNewPrevid</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Invoked every time a records status was changed.
                Please note that the event is only triggered on changes, not during a records creation.</td>
		</tr>		
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.statuschanged</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.8</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strSystemid<br />
                Root $objObject<br />
                string $intOldStatus<br />
                string $intNewStatus</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown if a records parent-id changed, e.g. if a record is moved within a hierarchical tree.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.recorddeleted</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strSystemid &nbsp;&nbsp;<br />
			string $strSourceClass</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown as soon as record is deleted from the database. Listen to those events if you want to trigger additional cleanups or delete linked contents.<br />Make sure to return a matching boolean-value, otherwise the transaction may be rolled back.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.recorddeleted.logically</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.8</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strSystemid &nbsp;&nbsp;<br />
			string $strSourceClass</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown as soon as record is deleted logically, so set inactive. The reocord is NOT removed from the database. Listen to those events if you want to trigger additional cleanups or delete linked contents.<br />Make sure to return a matching boolean-value, otherwise the transaction may be rolled back.</td>
		</tr>
		<tr>
        			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.recordrestored.logically</td>
        			<td style="border-top: 1px solid #ccc;">Since</td>
        			<td style="border-top: 1px solid #ccc;">4.8</td>
        		</tr>
        		<tr>
        			<td>Arguments</td>
        			<td>string $strSystemid<br />
                        string $strSourceClass The class-name of the object deleted<br />
                        \Kajona\System\System\Model $objObject The object which is being restored</td>
        		</tr>
        		<tr>
        			<td>Description</td>
        			<td>Called whenever a records is restored from the database.<br />The event is fired after the record was restored but before the transaction will be committed.<br />Make sure to return a matching boolean-value, otherwise the transaction may be rolled back.</td>
        		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.userfirstlogin</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strUserid</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown if a users logs into the system for the very first time. May be used to trigger initializations such as creating dashboard widgets or welcome messages.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.userlogin</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strUserid</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown if a users logs into the system. May be used to trigger initializations.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.system.userlogout</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>string $strUserid</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown if a users logs out of the system. May be used to trigger cleanup actions.</td>
		</tr>
		<tr>
			<td style="border-right: 1px solid #ccc;vertical-align: top;">&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.search.objectindexed</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">4.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>\Kajona\System\System\Model $objInstance<br />
			SearchDocument&nbsp;​$objSearchDocument</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Thrown as soon as an object is indexed by the search. Listen to this event if you want to add additional keywords to the objects' search index entry.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">core.packagemanager.packageupdated</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">5.1</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>@param \Kajona\Packagemanager\System\PackagemanagerPackagemanagerInterface $objManager the installed / updated package</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Event thrown as soon as a package was either installed or updated.</td>
		</tr>
		<tr>
			<td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">fileindexer.system.index.completed</td>
			<td style="border-top: 1px solid #ccc;">Since</td>
			<td style="border-top: 1px solid #ccc;">6.5</td>
		</tr>
		<tr>
			<td>Arguments</td>
			<td>
			    <dl>
			        <dt><code>\Kajona\Mediamanager\System\MediamanagerRepo $objRepo</code></dt>
			        <dd>The affected mediamaanger repository</dd>
			        <dt><code>\Kajona\Mediamanager\System\MediamanagerFile[] $arrFiles</code></dt>
			        <dd>Array of changed mediamanager files. The property <code>$strSearchContent</code> contains now the text content of the referenced file.</dd>
			    </dl>
			</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>Event thrown if the fileindexer has updated files in a repository.</td>
		</tr>
        <tr>
            <td rowspan="3" style="border-top: 1px solid #ccc; border-right: 1px solid #ccc;vertical-align: top;">flow.transition.executed</td>
            <td style="border-top: 1px solid #ccc;">Since</td>
            <td style="border-top: 1px solid #ccc;">7.0</td>
        </tr>
        <tr>
            <td>Arguments</td>
            <td>
                <dl>
                    <dt><code>\Kajona\System\System\Model $object</code></dt>
                    <dd>Contains the object on which we execute the status change</dd>
                    <dt><code>\Kajona\Flow\System\FlowTransition $transition</code></dt>
                    <dd>Contains the transition which gets executed.</dd>
                </dl>
            </td>
        </tr>
        <tr>
            <td>Description</td>
            <td>Callback method in case a transition was executed. The event gets triggered after every action and the handler
            code was executed. Note the event is triggered inside a transaction this means if the event throws an error the
            complete status change gets reverted.</td>
        </tr>
	</tbody>
</table>

# Frontend Events
There are certain java script modules which throw custom (jQuery) events.
<table>
	<tbody>
		<tr>
			<th>Identifier</th>
			<th>Arguments</th>
			<th>Description</th>
			<th>Since</th>
		</tr>
		<tr>
			<td>kajona.instantsave.updated</td>
			<td>result, systemid</td>
			<td>Fired as soon as the instantsave manager finished updating a property at the backend. The result is either `success`or `error`, the second params is the records systemid.</td>
			<td>6.5</td>
		</tr>
		<tr>
            <td>kajona.forms.mandatoryAdded</td>
            <td></td>
            <td>event thrown in case an element was added a `mandatoryFormElement` class, so marked as mandatory.</td>
            <td>7.0</td>
        </tr>
	</tbody>
</table>

# Commands

## Produce
```php
<?php

namespace Kajona\Module\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\Messagequeue\Command\CallEventCommand;

$event = new CallEventCommand('my_event', ['foo', 'bar']);

/** @var \Kajona\System\System\Messagequeue\Producer $producer */
$producer = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::MESSAGE_QUEUE_PRODUCER);
$producer->dispatch($event);

```

## Reference

<table>
	<tbody>
		<tr>
			<th>Class</th>
			<th>Description</th>
		</tr>
		<tr>
			<td><code>Kajona\System\System\Messagequeue\Command\CallEventCommand</code></td>
			<td>Triggers an system event at the background.</td>
		</tr>
    </tbody>
</table>
