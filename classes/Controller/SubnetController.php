<?php

namespace Dhcp\Controller;

use Dhcp\Model\SubnetModel;
use Dhcp\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Class SubnetController
 *
 * @author  Pavle Obradovic <pobradovic08@gmail.com>
 */
class SubnetController extends BaseController {

    /**
     * Get all subnets
     *
     * @param ServerRequestInterface $request Not used
     * @param ResponseInterface $response
     * @param array $args Not used
     * @return ResponseInterface
     */
    public function get_subnets (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $subnets = SubnetModel::all();
        $this->r->setData($subnets);
        $this->r->success();
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }

    /**
     * Get single subnet by ID
     *
     * @param ServerRequestInterface $request Not used
     * @param ResponseInterface $response
     * @param array $args Should contain 'subnet_id' key
     * @return ResponseInterface
     */
    public function get_subnet_by_id (ServerRequestInterface $request, ResponseInterface $response, $args) {
        if (!Validator::validateArgument($args, 'subnet_id', Validator::ID)) {
            $this->ci->logger->addError("Called " . __FUNCTION__ . "with invalid ID");
            $this->r->fail(400, "Invalid subnet ID");
            return $response->withStatus($this->r->getCode())->withJson($this->r);
        }
        try {
            $result = SubnetModel::findOrFail($args['subnet_id']);
            $this->r->success();
            $this->r->setData($result);
        } catch (ModelNotFoundException $e) {
            $this->r->fail(404, "Subnet with ID #{$args['subnet_id']} not found.");
        }
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }

    /**
     * Get subnet for VLAN ID
     *
     * @param ServerRequestInterface $request Not used
     * @param ResponseInterface $response
     * @param array $args Should contain 'vlan_id' key
     * @return ResponseInterface
     */
    public function get_subnet_by_vlan (ServerRequestInterface $request, ResponseInterface $response, $args) {
        if (!Validator::validateArgument($args, 'vlan_id', Validator::VLAN)) {
            $this->r->fail(400, 'Invalid VLAN ID.');
            return $response->withStatus($this->r->getCode())->withJson($this->r);
        }
        $result = SubnetModel::where('vlan', '=', $args['vlan_id'])->first();
        if ($result) {
            $this->r->success();
            $this->r->setData($result);
        } else {
            $this->r->fail(404, "Subnet with VLAN ID #{$args['vlan_id']} not found.");
        }
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }

    /**
     * Get subnet for IP address
     *
     * @param ServerRequestInterface $request Not used
     * @param ResponseInterface $response
     * @param array $args Should contain 'ip' key
     * @return ResponseInterface
     */
    public function get_subnet_by_address (ServerRequestInterface $request, ResponseInterface $response, $args) {
        if (!Validator::validateArgument($args, 'ip', Validator::IP)) {
            $this->r->fail(400, 'Invalid IP address');
            return $response->withStatus($this->r->getCode())->withJson($this->r);
        }
        $result = SubnetModel::where('from_address', '<=', ip2long($args['ip']))
                             ->where('to_address', '>=', ip2long($args['ip']))->first();
        if ($result) {
            $this->r->success();
            $this->r->setData($result);
        } else {
            $this->r->fail(404, "Subnet for address {$args['ip']} not found.");
        }
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }

    /**
     * Get list of free IP addresses in a subnet
     *
     * @param ServerRequestInterface $request Not used
     * @param ResponseInterface $response
     * @param array $args Should contain 'subnet_id' key
     * @return ResponseInterface Returns array of free IP addresses (as strings)
     */
    public function get_subnet_free_addresses (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $addresses = [];
        $reserved_addresses = [];
        if (!Validator::validateArgument($args, 'subnet_id', Validator::ID)) {
            $this->ci->logger->addError("Called " . __FUNCTION__ . "with invalid ID");
            $this->r->fail(400, "Invalid subnet ID");
            return $response->withStatus($this->r->getCode())->withJson($this->r);
        }
        try {
            $result = SubnetModel::findOrFail($args['subnet_id']);
            $reservations = $this->ci->capsule->table('groups')
                                        ->select('ip')
                                        ->join('reservations', 'groups.group_id', 'reservations.group_id')
                                        ->join('subnets', 'subnets.subnet_id', 'groups.subnet_id')
                                        ->where('subnets.subnet_id', '=', $args['subnet_id'])
                                        ->get();
            foreach($reservations as $reservation){
                $reserved_addresses[] = $reservation->ip;
            }
            for ($i = ip2long($result->from_address); $i <= ip2long($result->to_address); $i++) {
                if (!in_array($i, $reserved_addresses)) {
                    $addresses[] = long2ip($i);
                }
            }
            $this->r->success();
            $this->r->setData($addresses);
        } catch (ModelNotFoundException $e) {
            $this->r->fail(404, "Subnet with ID #{$args['subnet_id']} not found.");
        }
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }

    //TODO: new subnet
    public function post_subnet (ServerRequestInterface $request, ResponseInterface $response, $args) {

    }

    //TODO: update subnet
    public function put_subnet (ServerRequestInterface $request, ResponseInterface $response, $args) {

    }

    //TODO: delete subnet - check constraints
    public function delete_subnet (ServerRequestInterface $request, ResponseInterface $response, $args) {
        if (!Validator::validateArgument($args, 'subnet_id', Validator::ID)) {
            $this->ci->logger->addError("Called " . __FUNCTION__ . "with invalid ID");
            $this->r->fail(400, "Invalid subnet ID");
            return $response->withStatus($this->r->getCode())->withJson($this->r);
        }
        try {
            $result = SubnetModel::findOrFail($args['subnet_id']);
            if($result->delete()){
                $this->r->success("Subnet {$result->subnet_id} deleted.");
            }else{
                $this->r->fail(500, "Couldn't delete subnet");
            }
        } catch (ModelNotFoundException $e) {
            $this->r->fail(404, "Subnet with ID #{$args['subnet_id']} not found.");
        }
        return $response->withStatus($this->r->getCode())->withJson($this->r);
    }
}