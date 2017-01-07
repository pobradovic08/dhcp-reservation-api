<?php

namespace Dhcp\Reservation;

use \Interop\Container\ContainerInterface as ContainerInterface;

class ReservationController {
    protected $ci;

    //Constructor
    public function __construct (ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function get_reservations ($request, $response, $args) {
        $this->ci->logger->addInfo("Reservation list");
        return $this->get_filtered_reservations($response, [], true, $args['mode'] == 'terse');
    }

    public function get_reservations_for_subnet ($request, $response, $args) {
        $this->ci->logger->addInfo("Reservation list for subnet #" . $args['subnet_id']);
        // Filter data
        $filter = ['subnet_id' => $args['subnet_id']];
        return $this->get_filtered_reservations($response, $filter, true, $args['mode'] == 'terse');
    }

    public function get_reservations_for_group ($request, $response, $args) {
        $this->ci->logger->addInfo("Reservation list for subnet #" . $args['subnet_id']);
        $filter = ['group_id' => $args['group_id']];
        return $this->get_filtered_reservations($response, $filter, true, $args['mode'] == 'terse');
    }

    public function get_reservation_by_ip ($request, $response, $args) {
        $this->ci->logger->addInfo('Request for reservation with IP: ' . $args['ip']);
        $filter = ['ip' => $args['ip']];
        return $this->get_filtered_reservations($response, $filter, false, $args['mode'] == 'terse');
    }

    public function get_reservation_by_id ($request, $response, $args) {
        $this->ci->logger->addInfo('Request for reservation #' . $args['id']);
        $filter = ['id' => $args['id']];
        return $this->get_filtered_reservations($response, $filter, false, $args['mode'] == 'terse');
    }

    public function get_reservation_by_mac ($request, $response, $args) {
        $this->ci->logger->addInfo('Request for reservation with MAC: ' . $args['mac']);

        $clean_mac = preg_replace('/[\.:-]/', '', $args['mac']);
        $filter = ['mac' => intval($clean_mac, 16)];
        return $this->get_filtered_reservations($response, $filter, true, $args['mode'] == 'terse');
    }

    public function post_reservation ($request, $response, $args) {
    }

    public function put_reservation ($request, $response, $args) {
    }

    public function delete_reservation ($request, $response, $args) {
    }

    private function get_filtered_reservations ($response, $filter, $multiple_results = false, $terse = false) {
        $r = new \Dhcp\Response();
        $mapper = new ReservationMapper($this->ci->db);
        $reservations = $mapper->getReservations($filter, $terse);
        /*
         * For multiple results success is imminent :)
         * If there's no reservations we just return empty array
         * For single results, fail if not found with 404 http code
         */
        if ($multiple_results) {
            $array = [];
            foreach ( $reservations as $reservation ) {
                $array[] = $reservation->serialize();
            }
            $r->success();
            $r->setData($array);
            return $response->withStatus($r->getCode())->withJson($r);
        } else {
            if (sizeof($reservations) == 1) {
                $r->success();
                $r->setData($reservations[0]->serialize());
            } else {
                $r->fail();
                $r->setCode(404);
            }
            return $response->withStatus($r->getCode())->withJson($r);
        }
    }
}