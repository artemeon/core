<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace AGP\Dashboard\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Dashboard\System\ServiceICalGenerator;
use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Exceptions\EntityNotFoundException;
use Kajona\System\System\Exceptions\WrongSystemIdException;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;

/**
 * CalendarApiController
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 */
class CalendarApiController implements ApiControllerInterface
{
    /**
     * @inject dashboard_ical_generator
     * @var ServiceICalGenerator
     */
    private $iCalGenerator;

    /**
     * Returns internet calendar by token
     *
     * @api
     * @method GET
     * @path /v1/calendar/export/caldav/{token}
     * @authorization anonymous
     */
    public function caldav(HttpContext $context)
    {
        try {
            $headers = [
                'Content-Type' => 'text/calendar',
                'Content-Disposition' => 'attachment; filename="agpCalendar.ics"'
            ];

            $body = $this->iCalGenerator->generate($context->getUriFragment('token'));

            return new HttpResponse(200, $headers, $body);
        } catch (WrongSystemIdException $e) {
            return new HttpResponse(400, [], '');
        } catch (EntityNotFoundException $e) {
            return new HttpResponse(404, [], '');
        } catch (AuthenticationException $e) {
            return new HttpResponse(401, [], '');
        }
    }
}
