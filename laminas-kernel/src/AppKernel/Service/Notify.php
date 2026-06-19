<?php

/*
 * @see http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/introduction.html
 */

namespace AppKernel\Service;

class Notify {

    /**
     *     @SWG\Post(
     *     path="sendMessage",
     *     summary="Gui thong bao",
     *     tags={"Notifications"},
     *     description="sendMessage",
     *     operationId="sendMessage",
     *     produces={"application/x-www-form-urlencoded;charset=utf-8;"},     *    
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(property="total",type="integer",),
     *              @SWG\Property(property="code",type="integer",),
     *              @SWG\Property(
     *                  property="cities",type="array",
     *                    @SWG\Items(ref="#/definitions/AppLocationCities")
     *              ),
     *              
     *         )
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="Elasticsearch is invalid",
     *         ref="#/definitions/resp"
     *     ),     
     * )
     */
    

    // $response = sendMessage();
    // $return["allresponses"] = $response;
    // $return = json_encode($return);
    // print("\n\nJSON received:\n");
    // print($return);
    // print("\n");
}
