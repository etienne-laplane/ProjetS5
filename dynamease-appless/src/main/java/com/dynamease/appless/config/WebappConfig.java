/*
 * Copyright 2013 the original author or authors.
 *
 */
package com.dynamease.appless.config;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.ComponentScan;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.PropertySource;
import org.springframework.core.env.Environment;
import org.springframework.web.servlet.config.annotation.EnableWebMvc;

import com.dynamease.request.services.DynCallRequestHandler;


/**
 * Main configuration class for the application.
 * Turns on @Component scanning, loads externalized application.properties
 * @author Etienne Laplane
 */
@Configuration
@ComponentScan(basePackages = {"com.dynamease.appless"})
//@ComponentScan(basePackages = {"com.dynamease.appless", "com.dynamease.request.services", "com.dynamease.request.reqprocessors"})
@EnableWebMvc
public class WebappConfig {
    
    private static final Logger logger = LoggerFactory.getLogger(WebappConfig.class);

    
    @Autowired
    private CallRequestConfig crconfig;
    
    @Bean
    public DynCallRequestHandler callRequestHandler() {
        logger.debug(String.format("Creating CallRequest Handler with %s", crconfig.getClass().toString()));
        return crconfig.callRequestsHandler();
    }
    
     
   

   
    
}
