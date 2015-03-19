package com.dynamease.appless.web;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;
import java.net.URLEncoder;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Locale;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;

import org.apache.commons.httpclient.URIException;
import org.apache.commons.httpclient.util.URIUtil;
import org.h2.util.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.web.servlet.ModelAndView;

import com.dynamease.core.entity.DynContactInteractivityType;
import com.dynamease.core.entity.DynPerson;
import com.dynamease.core.entity.DynCategories;
import com.dynamease.core.entity.DynComMean;
import com.dynamease.core.entity.DynContact;
import com.dynamease.core.entity.DynPerson;
import com.dynamease.core.entity.DynSubscriber;
import com.dynamease.core.repository.DynUserInDbDao;
import com.dynamease.core.repository.UserNotFoundInDbException;
import com.dynamease.core.repository.entities.DynUserInDb;
import com.dynamease.core.services.DynCorporateSubOperations;
import com.dynamease.core.services.DynServicesException;
import com.dynamease.core.services.DynSubscriberOperations;
import com.dynamease.core.services.ldap.ContactNotFoundException;
import com.dynamease.core.services.ldap.DynContactDaoInterface;
import com.dynamease.core.services.ldap.DynInvalidSubIdException;
import com.dynamease.request.services.DynCallLog;
import com.dynamease.request.services.DynCallLogInvalidOperation;
import com.dynamease.request.services.DynCallParam;
import com.dynamease.request.services.DynCallRequest;
import com.dynamease.request.services.DynCallRequestHandler;
import com.dynamease.request.services.DynCallResponse;
import com.dynamease.request.services.DynLogsOperation;
import com.dynamease.request.services.DynUpdateContactRequest;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import edu.emory.mathcs.backport.java.util.Collections;

/**
 * Define the available operations to contact a Subscriber from the client application and to
 * retrieve the history of received calls.
 * 
 * 
 */

// TODO : Mieux traiter les DynServicesException générées par la crypto des URL.
// Pour l'instant
// elles sont justes jetées par les fonctions JSON

@Controller
@RequestMapping(value = "/communication")
public class CommunicationController {
    private static final Logger logger = LoggerFactory.getLogger(CommunicationController.class);

    private static final String UTF8 = "UTF-8";

    @Autowired
    private DynCallRequestHandler callRequestsHandler;

    @Autowired
    private DynLogsOperation dynLogOp;


    /**
     * Receive a call request and answer which action to perform to the Dynamease application.
     * 
     * @param ids
     *            DynCallRequest in Json form.
     * @return DynCallResponse in Json form.
     */
    @RequestMapping(headers = { "content-type=application/json" }, method = RequestMethod.POST)
    @ResponseBody
    public String communicate(@RequestBody String ids) {
        logger.debug("A new call request! With args: " + ids);

        String callerNumber;
        String calledNumber;
        DynCallParam callParameters = null;

        DynPerson caller = null;
        DynSubscriber called = null;
        DynCallResponse response = null;

        try {
            callParameters = new ObjectMapper().readValue(ids, DynCallParam.class);
            callerNumber = callParameters.getCallerNumber();
            calledNumber = callParameters.getCalledNumber();

            DynCallRequest request = new DynCallRequest(callerNumber, calledNumber, null, null, null);
            response = callRequestsHandler.reachRequest(request);

            caller = request.getSender();
            called = request.getReceiver();

            dynLogOp.logCall(request, response);

            logger.info("\nContact: " + caller + " \nWants to join: " + called + "\n\tAnswering: " + response);

        } catch (IOException e) {
            logger.warn("Json badly recognized", e);
            response = new DynCallResponse(DynContactInteractivityType.REFUSED, null, null);
            response.setInteractivityInfo("Json not recognized");
        } catch (Exception e) {
            logger.error(String.format("Unhandled exception occured during call processing : %s.", e.toString()));
            if (response == null) {
                response = new DynCallResponse(DynContactInteractivityType.REFUSED, null, null);
                response.setSubName("Votre correspondant");
                response.setInteractivityInfo(e.getMessage());
            }

        }

        String returnedString = "";

        logger.debug(response.toString() + response.getSubName());
        response.selfClean();
        try {
            returnedString = new ObjectMapper().writeValueAsString(response);
        } catch (JsonProcessingException e) {
            logger.error(String.format("Json Processing Error in building response : %s", e.toString()), e);
        }
        logger.debug("Returned Json: " + returnedString);
        return returnedString;
    }

    /**
     * Same as communicate, just change of content type to match request form from Restcomm
     * 
     * @param ids
     *            DynCallRequest in Json form.
     * @return DynCallResponse in Json form.
     */
    @RequestMapping(headers = { "content-type=application/x-www-form-urlencoded" }, method = RequestMethod.POST)
    @ResponseBody
    public String restcommCommunicate(@RequestBody String ids) {
        // TODO : refactor to avoid duplicate method restcomm Communicate and communicate
        logger.debug("A new call request! With args: " + ids);

        String callerNumber;
        String calledNumber;

        // This is a temporary workaround to avoid bad character transmission from Restcom (%2B is a +)
        // TODO : rethink it the best way with Url encoding (to be checked with Restcom)
        DynCallParam callParameters = new DynCallParam();
        String temp = ids.split("&")[0].split("=")[1];
        callerNumber = StringUtils.urlDecode(temp);
//        callerNumber = temp.replaceAll("%2B", "");
        callParameters.setCallerNumber(callerNumber);
        temp = ids.split("&")[1].split("=")[1];
        calledNumber = StringUtils.urlDecode(temp);
//        calledNumber = temp.replaceAll("%2B", "");;
        callParameters.setCalledNumber(callerNumber);
      

        DynPerson caller = null;
        DynSubscriber called = null;
        DynCallResponse response = null;

        try {
            
           
            
            DynCallRequest request = new DynCallRequest(callerNumber, calledNumber, null, null, null);
            response = callRequestsHandler.reachRequest(request);

            caller = request.getSender();
            called = request.getReceiver();

            dynLogOp.logCall(request, response);

            logger.info("\nContact: " + caller + " \nWants to join: " + called + "\n\tAnswering: " + response);

        } catch (Exception e) {
            logger.error(String.format("Unhandled exception occured writing call logs : %s.", e.toString()));
            if (response == null) {
                response = new DynCallResponse(DynContactInteractivityType.REFUSED, null, null);
                response.setInteractivityInfo(e.getMessage());
            }

        }

        String returnedString = "";

        logger.debug(response.toString() + response.getSubName());
        response.selfClean();
        try {
            returnedString = new ObjectMapper().writeValueAsString(response);
        } catch (JsonProcessingException e) {
            logger.error(String.format("Json Processing Error in building response : %s", e.toString()), e);
        }
        logger.debug("Returned Json: " + returnedString);
        return returnedString;
    }
    

}
