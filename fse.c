#include <CoreServices/CoreServices.h>
#include <unistd.h>
#include <stdio.h>
#include <sys/stat.h>

void myCallbackFunction(
ConstFSEventStreamRef streamRef,
void *clientCallBackInfo,
size_t numEvents,
void *eventPaths,
const FSEventStreamEventFlags eventFlags[],
const FSEventStreamEventId eventIds[])
{
    int i;
    char **paths = eventPaths;
	
    // printf("Callback called\n");
    for (i=0; i<numEvents; i++) {
        int count;
        /* flags are unsigned long, IDs are uint64_t */
        printf("%llu:%lu:%s\n", eventIds[i], eventFlags[i], paths[i]);
	}
}

void show(CFStringRef formatString, ...) {
	CFStringRef resultString;
	CFDataRef data;
	va_list argList;
	
	va_start(argList, formatString);
	resultString = CFStringCreateWithFormatAndArguments(NULL, NULL, 
	formatString, argList);
	va_end(argList);
	
	data = CFStringCreateExternalRepresentation(NULL, resultString, 
	CFStringGetSystemEncoding(), '?');
	
	if (data != NULL) {
		printf ("%.*s\n\n", (int)CFDataGetLength(data), 
		CFDataGetBytePtr(data));
		CFRelease(data);
	}
	CFRelease(resultString);
}

int
main(int argc, char *argv[])
{
    /* Define variables and create a CFArray object containing
	CFString objects containing paths to watch.
	*/
    CFStringRef mypath = CFSTR("/");
    CFArrayRef pathsToWatch = CFArrayCreate(NULL, (const void **)&mypath, 1, NULL);
    void *callbackInfo = NULL; // could put stream-specific data here.
    FSEventStreamRef stream;
    CFAbsoluteTime latency = 3.0; /* Latency in seconds */
	struct stat Status;
 	stat("/", &Status);
	dev_t device = Status.st_dev;
	
	
	CFUUIDRef uuidO;
	CFStringRef uuid;
	uuidO = FSEventsCopyUUIDForDevice(device); 
	uuid = CFUUIDCreateString(NULL, uuidO);
	
	show(CFSTR("%@:256"), uuid);
	
	
	
    /* Create the stream, passing in a callback, */
    stream =  FSEventStreamCreateRelativeToDevice(NULL,
	&myCallbackFunction,
	callbackInfo,
	device,
	pathsToWatch,
	atoi(argv[2]), /* Or a previous event ID */
	latency,
	kFSEventStreamCreateFlagNone /* Flags explained in reference */
	);
	  /* Create the stream before calling this. */
    FSEventStreamScheduleWithRunLoop(stream, CFRunLoopGetCurrent(),         kCFRunLoopDefaultMode);
	FSEventStreamStart(stream);
	//CFRunLoopRun();
	CFRunLoopRunInMode(kCFRunLoopDefaultMode,atoi(argv[1]),false);
	//sleep(10);
	
	
  
}



