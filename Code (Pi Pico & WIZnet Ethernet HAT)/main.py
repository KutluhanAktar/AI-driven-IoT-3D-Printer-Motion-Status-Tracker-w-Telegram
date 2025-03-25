# AI-driven IoT 3D Printer Motion & Status Tracker w/ Telegram

# Windows, Linux, or Ubuntu

# By Kutluhan Aktar

# Track lateral and vertical movements of a 3D printer via AprilTags. Then, get informed of malfunctions related to motion via Telegram.

# For more information:
# https://www.theamplituhedron.com/projects/IoT_3D_Printer_Motion_Status_Tracker_w_Telegram/

import board
import busio
import digitalio
import time
import adafruit_requests as requests
from adafruit_wiznet5k.adafruit_wiznet5k import WIZNET5K
import adafruit_wiznet5k.adafruit_wiznet5k_socket as socket
from huskylib import HuskyLensLibrary
from time import sleep
from machine import Pin, PWM

class IoT_3D_printer_tracker:
    def __init__(self, SPI0_SCK, SPI0_TX, SPI0_RX, SPI0_CSn, W5x00_RSTn):
        # Define the PHP web application path.
        self.App = "http://192.168.1.20/telegram_3D_printer_bot/"
        # Setup the network configuration settings:
        MY_MAC = (0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED)
        IP_ADDRESS = (192, 168, 1, 24)
        SUBNET_MASK = (255, 255, 255, 0)
        GATEWAY_ADDRESS = (192, 168, 1, 1)
        DNS_SERVER = (8, 8, 8, 8)
        # Define the WIZnet Ethernet HAT pin settings:
        ethernetRst = digitalio.DigitalInOut(W5x00_RSTn)
        ethernetRst.direction = digitalio.Direction.OUTPUT
        cs = digitalio.DigitalInOut(SPI0_CSn)
        spi_bus = busio.SPI(SPI0_SCK, MOSI=SPI0_TX, MISO=SPI0_RX)
        # Reset the WIZnet Ethernet HAT.
        ethernetRst.value = False
        sleep(1)
        ethernetRst.value = True    
        # Initialize the Ethernet interface without DHCP.
        self.eth = WIZNET5K(spi_bus, cs, is_dhcp=False, mac=MY_MAC, debug=False)
        # Set the network configuration.
        self.eth.ifconfig = (IP_ADDRESS, SUBNET_MASK, GATEWAY_ADDRESS, DNS_SERVER)
        # Optional: Initialize the Ethernet interface with DHCP.
        #eth = WIZNET5K(spi_bus, cs, is_dhcp=True, mac=MY_MAC, debug=False)
        # Print the WIZnet Ethernet HAT information.
        print("Chip Version: ", self.eth.chip)
        print("\nMAC Address: ", [hex(i) for i in self.eth.mac_address])
        print()
        # Define HuskyLens settings.
        self.husky_lens = HuskyLensLibrary("I2C", Pin(4), Pin(5), 0x32)
        # Define the RGB LED settings:
        self.red = PWM(Pin(10))
        self.green = PWM(Pin(11))
        self.blue = PWM(Pin(12))
        self.red.freq(1000) 
        self.green.freq(1000)
        self.blue.freq(1000)
        # Define the buzzer:
        self.buzzer = PWM(Pin(13))
        self.buzzer.freq(450)
        self.adjust_color(0, 0, 65025)
    # Detect the 3D printer lateral and vertical motions via tags (AprilTags). Then, transfer the current printer motions to the given web application.
    def detect_and_transfer_motions(self, rest):
            detected_tag = self.husky_lens.command_request_blocks_learned()
            tag_number = len(detected_tag)
            if (tag_number == 3):
                self.adjust_color(65025, 65025, 0)
                x_axis = str(detected_tag[0][0]) + "," + str(detected_tag[0][1])
                y_axis = str(detected_tag[1][0]) + "," + str(detected_tag[1][1])
                z_axis = str(detected_tag[2][0]) + "," + str(detected_tag[2][1])
                print()
                print("X-Axis: " + x_axis)
                print("Y-Axis: " + y_axis)
                print("Z-Axis: " + z_axis)
                # Define the parameters to transfer printer movements to the web application.
                parameters = {"x_axis" : x_axis, "y_axis" : y_axis, "z_axis" : z_axis}
                # Initialize a requests object with the Ethernet interface and a socket.
                requests.set_socket(socket, self.eth)
                print()
                # Make an HTTP POST request to the web application.
                r = requests.post(self.App, data=parameters)
                print("-" * 60)
                print("Data Saved and Transferred to the Telegram Bot Successfully!")
                print("-" * 60)
                r.close()
                # Notify the user after sending the current printer motions.
                self.adjust_color(0, 65025, 0)
                self.buzzer.duty_u16(65025)
                sleep(5)
                self.buzzer.duty_u16(0)
                self.adjust_color(65025, 0, 65025)
            else:
                print(detected_tag)
            # Maintain the DHCP lease.
            self.eth.maintain_dhcp_lease()
            sleep(rest)
    # Change the RGB LED's color. 
    def adjust_color(self, red_x, green_x, blue_x):
        self.red.duty_u16(65025-red_x)
        self.green.duty_u16(65025-green_x)
        self.blue.duty_u16(65025-blue_x)
                
# Define the new 'tracker' class object.
tracker = IoT_3D_printer_tracker(board.GP18, board.GP19, board.GP16, board.GP17, board.GP15)
        
while True:
    tracker.detect_and_transfer_motions(30)
