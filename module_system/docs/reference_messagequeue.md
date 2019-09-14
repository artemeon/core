
# Message-Queue

In the AGP a user sometimes issues a task which requires a costly operation. To execute
this operation in the background and not in the user request we have a simple message
queue system which allows you to create a command which gets executed in the background.
At the moment the message queue is simply based on a table which gets executed in the
background through a cron but in the future if we have the fitting infrastructure we
could also move to a real message queue system.

## Flow

If you have a new task which should be executed in the background you need to create
a new command. A command is a simple value object which holds all information to execute a specific
command. Your command should have a descriptive class name which describes what it does i.e.
`SendMessageCommand` or `CallHttpEndpointCommand`. Every command must implement the
`Kajona\System\System\Messagequeue\CommandInterface`. You can specify through the `@executor`
annotation a specific service from the DI container which is responsible to execute
this command. The service must implement the `Kajona\System\System\Messagequeue\ExecutorInterface`.
