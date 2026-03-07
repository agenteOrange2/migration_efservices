<?php

namespace App\Traits;

use App\Models\Carrier;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ValidatesCarrierOwnership
{
    /**
     * Get the authenticated carrier from the current user.
     *
     * @return \App\Models\Carrier
     * @throws \Exception
     */
    protected function getAuthenticatedCarrier(): Carrier
    {
        $user = Auth::user();
        
        if (!$user || !$user->carrierDetails || !$user->carrierDetails->carrier) {
            abort(Response::HTTP_FORBIDDEN, 'No carrier associated with authenticated user.');
        }
        
        return $user->carrierDetails->carrier;
    }
    
    /**
     * Validate that a resource belongs to the authenticated carrier.
     *
     * @param mixed $resource The resource to validate (must have carrier_id property)
     * @param string|null $errorMessage Custom error message
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validateCarrierOwnership($resource, ?string $errorMessage = null): void
    {
        $carrier = $this->getAuthenticatedCarrier();
        
        if (!property_exists($resource, 'carrier_id') && !isset($resource->carrier_id)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Resource does not have carrier_id property.');
        }
        
        if ((int) $resource->carrier_id !== (int) $carrier->id) {
            abort(
                Response::HTTP_FORBIDDEN, 
                $errorMessage ?? 'Unauthorized access to resource.'
            );
        }
    }
    
    /**
     * Validate that multiple resources belong to the authenticated carrier.
     *
     * @param iterable $resources Collection or array of resources to validate
     * @param string|null $errorMessage Custom error message
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validateCarrierOwnershipBulk(iterable $resources, ?string $errorMessage = null): void
    {
        $carrier = $this->getAuthenticatedCarrier();
        
        foreach ($resources as $resource) {
            if (!property_exists($resource, 'carrier_id') && !isset($resource->carrier_id)) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Resource does not have carrier_id property.');
            }
            
            if ((int) $resource->carrier_id !== (int) $carrier->id) {
                abort(
                    Response::HTTP_FORBIDDEN, 
                    $errorMessage ?? 'Unauthorized access to one or more resources.'
                );
            }
        }
    }
    
    /**
     * Check if a resource belongs to the authenticated carrier without aborting.
     *
     * @param mixed $resource The resource to check
     * @return bool
     */
    protected function belongsToAuthenticatedCarrier($resource): bool
    {
        try {
            $carrier = $this->getAuthenticatedCarrier();
            
            if (!property_exists($resource, 'carrier_id') && !isset($resource->carrier_id)) {
                return false;
            }
            
            return (int) $resource->carrier_id === (int) $carrier->id;
        } catch (\Exception $e) {
            return false;
        }
    }
}
